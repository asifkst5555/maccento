<?php

namespace App\Services;

use App\Jobs\SendLeadNotificationJob;
use App\Models\AiUsageLog;
use App\Models\Conversation;
use App\Models\LeadEvent;
use App\Models\LeadProfile;
use App\Models\Message;
use App\Services\AI\AiProviderManager;
use Illuminate\Support\Arr;

class ChatOrchestrator
{
    public function __construct(
        private readonly LeadExtractionService $leadExtractionService,
        private readonly AiProviderManager $aiProviderManager,
        private readonly MaccentoKnowledgeService $knowledgeService,
        private readonly PackageBuilderPricingService $pricingService,
    ) {
    }

    /**
     * @return array{assistant_message:Message,lead:LeadProfile,completed:bool,missing_fields:array<int,string>}
     */
    public function handleUserMessage(Conversation $conversation, string $content, ?string $preferredLanguage = null): array
    {
        $conversation->messages()->create([
            'role' => 'user',
            'content' => trim($content),
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();
        $assistantLanguage = $this->resolveAssistantLanguage($conversation, $preferredLanguage, $content);
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $metadata['language'] = $assistantLanguage;
        $conversation->metadata = $metadata;
        $conversation->save();

        $lead = $conversation->leadProfile()->firstOrCreate([]);
        $extracted = $this->leadExtractionService->extract($content);
        $this->mergeLeadData($lead, $extracted);

        $conversationMetadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $existingPackageDraft = is_array(Arr::get($conversationMetadata, 'package_draft')) ? Arr::get($conversationMetadata, 'package_draft') : [];
        $hasPackageIntent = $this->hasPackageIntent($content, $existingPackageDraft);
        $packageDraft = $existingPackageDraft;
        if ($hasPackageIntent) {
            $packageDraft = $this->mergePackageDraft(
                $existingPackageDraft,
                $this->extractPackageDraftFromMessage($content),
            );
            $conversationMetadata['package_draft'] = $packageDraft;
            $conversation->metadata = $conversationMetadata;
            $conversation->save();

            if (!empty($packageDraft['services']) && is_array($packageDraft['services'])) {
                $lead->service_type = implode(',', $packageDraft['services']);
            }
            if (!blank($packageDraft['listing_type'] ?? null)) {
                $lead->property_type = (string) $packageDraft['listing_type'];
            }
        }

        $lead->score = $this->scoreLead($lead);

        $lastAssistant = $conversation->messages()
            ->where('role', 'assistant')
            ->latest('id')
            ->first();
        $awaitingConfirmation = ($lastAssistant?->metadata['type'] ?? null) === 'confirmation_request';
        $userConfirmed = $this->isConfirmation($content);
        $missingFields = $this->missingFields($lead, $hasPackageIntent);
        $packageQuote = $hasPackageIntent ? $this->calculatePackagePreview($packageDraft) : null;

        if ($awaitingConfirmation && $userConfirmed && $missingFields === []) {
            $lead->status = 'qualified';
            $lead->qualified_at = now();
            $lead->save();

            LeadEvent::create([
                'lead_profile_id' => $lead->id,
                'event_type' => 'lead_qualified',
                'payload' => ['source' => 'chat_orchestrator'],
            ]);

            SendLeadNotificationJob::dispatch($lead->id);

            $assistantText = $assistantLanguage === 'fr'
                ? 'Parfait. Votre demande est soumise et qualifiee. Notre equipe vous contactera rapidement.'
                : 'Perfect. Your request is submitted and marked as qualified. Our team will contact you shortly.';
            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $assistantText,
                'metadata' => ['type' => 'qualified_confirmation'],
            ]);

            return [
                'assistant_message' => $assistantMessage,
                'lead' => $lead->fresh(),
                'completed' => true,
                'missing_fields' => [],
            ];
        }

        $assistantMetadata = $missingFields === []
            ? ['type' => 'confirmation_request']
            : ['type' => 'follow_up', 'field' => $missingFields[0]];
        if ($hasPackageIntent) {
            $assistantMetadata['journey'] = 'package_builder_assist';
            $assistantMetadata['package_draft'] = $packageDraft;
            if ($packageQuote !== null) {
                $assistantMetadata['package_preview'] = [
                    'currency' => $packageQuote['currency'] ?? 'USD',
                    'total' => (int) ($packageQuote['total'] ?? 0),
                    'line_items' => $packageQuote['line_items'] ?? [],
                ];
            }
        }

        $baseAssistantText = $hasPackageIntent
            ? $this->buildPackageGuidance($packageDraft, $packageQuote, $lead, $missingFields, $assistantLanguage)
            : ($missingFields === []
                ? $this->buildSummaryRequest($lead, $assistantLanguage)
                : $this->followUpQuestion($missingFields[0], $lead, $assistantLanguage));

        $provider = $this->aiProviderManager->provider();
        $durationMs = 0;
        $usage = null;
        $assistantResponseText = $baseAssistantText;

        try {
            $aiStart = microtime(true);
            $usage = $provider->chat($this->buildAssistantPrompt(
                $conversation,
                $content,
                $lead,
                $missingFields,
                $baseAssistantText,
                $packageDraft,
                $packageQuote,
                $assistantLanguage,
            ));
            $durationMs = (int) ((microtime(true) - $aiStart) * 1000);
            $assistantResponseText = trim((string) ($usage['content'] ?? $baseAssistantText));

            AiUsageLog::create([
                'conversation_id' => $conversation->id,
                'provider' => $provider->name(),
                'model' => $usage['model'],
                'tokens_in' => $usage['tokens_in'],
                'tokens_out' => $usage['tokens_out'],
                'estimated_cost' => 0,
                'duration_ms' => max($durationMs, (int) ($usage['duration_ms'] ?? 0)),
            ]);
        } catch (\Throwable $e) {
            $assistantResponseText = $baseAssistantText;
        }

        // Enforce strict one-question flow while required fields are still missing.
        // This prevents LLM responses that ask multiple questions in a single message.
        if ($missingFields !== []) {
            $assistantResponseText = $baseAssistantText;
        }

        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $assistantResponseText,
            'model' => $usage['model'] ?? null,
            'tokens_in' => $usage['tokens_in'] ?? null,
            'tokens_out' => $usage['tokens_out'] ?? null,
            'latency_ms' => max($durationMs, (int) ($usage['duration_ms'] ?? 0)),
            'metadata' => $assistantMetadata,
        ]);

        $lead->save();

        return [
            'assistant_message' => $assistantMessage,
            'lead' => $lead->fresh(),
            'completed' => false,
            'missing_fields' => $missingFields,
        ];
    }

    private function mergeLeadData(LeadProfile $lead, array $extracted): void
    {
        foreach ($extracted as $field => $value) {
            if (!in_array($field, $lead->getFillable(), true)) {
                continue;
            }

            $isEmpty = $lead->{$field} === null || $lead->{$field} === '';
            if ($isEmpty && $value !== null && $value !== '') {
                $lead->{$field} = $value;
            }
        }

        if (!empty($extracted)) {
            $history = $lead->notes ? $lead->notes . "\n" : '';
            $lead->notes = trim($history . '[captured] ' . json_encode($extracted));
        }
    }

    /**
     * @return array<int, string>
     */
    private function missingFields(LeadProfile $lead, bool $packageMode = false): array
    {
        $missing = [];
        if (blank($lead->name)) {
            $missing[] = 'name';
        }
        if (blank($lead->email) && blank($lead->phone)) {
            $missing[] = 'contact';
        }
        if (blank($lead->service_type)) {
            $missing[] = 'service_type';
        }
        if (!$packageMode && blank($lead->location)) {
            $missing[] = 'location';
        }
        if (!$packageMode && blank($lead->timeline)) {
            $missing[] = 'timeline';
        }

        return $missing;
    }

    private function scoreLead(LeadProfile $lead): int
    {
        $score = 0;
        if (filled($lead->email) || filled($lead->phone)) {
            $score += 30;
        }
        if (filled($lead->service_type)) {
            $score += 20;
        }
        if (filled($lead->timeline)) {
            $score += 20;
        }
        if (filled($lead->budget_min) || filled($lead->budget_max)) {
            $score += 15;
        }
        if (filled($lead->decision_maker)) {
            $score += 15;
        }

        return min($score, 100);
    }

    private function followUpQuestion(string $field, LeadProfile $lead, string $language = 'en'): string
    {
        $fr = [
            'name' => 'Puis-je avoir votre nom complet pour preparer votre demande?',
            'contact' => 'Quel est le meilleur contact pour vous, email ou telephone?',
            'service_type' => 'De quel service avez-vous besoin: photo, drone, home staging virtuel, ou video walkthrough?',
            'location' => 'Quelle est l\'adresse ou la zone de la propriete?',
            'timeline' => 'Quand en avez-vous besoin (ASAP, cette semaine, semaine prochaine)?',
            'default' => 'Pouvez-vous partager un detail de plus pour finaliser la demande?',
        ];

        $en = [
            'name' => 'Can I have your full name so I can prepare your request?',
            'contact' => 'What is the best contact detail for you, email or phone?',
            'service_type' => 'Which service do you need: photography, drone, virtual staging, or video walkthrough?',
            'location' => 'What is the property location?',
            'timeline' => 'When do you need this done (for example ASAP, this week, or next week)?',
            'default' => 'Could you share one more detail so I can complete your booking request?',
        ];

        $map = $language === 'fr' ? $fr : $en;
        return match ($field) {
            'name' => $map['name'],
            'contact' => $map['contact'],
            'service_type' => $map['service_type'],
            'location' => $map['location'],
            'timeline' => $map['timeline'],
            default => $map['default'],
        };
    }

    private function buildSummaryRequest(LeadProfile $lead, string $language = 'en'): string
    {
        $contact = $lead->email ?: $lead->phone;

        if ($language === 'fr') {
            return sprintf(
                'Je note: nom %s, service %s, lieu %s, delai %s, contact %s. Repondez "yes" pour confirmer et soumettre.',
                $lead->name ?? '-',
                $lead->service_type ?? '-',
                $lead->location ?? '-',
                $lead->timeline ?? '-',
                $contact ?? '-',
            );
        }

        return sprintf(
            'I have: name %s, service %s, location %s, timeline %s, contact %s. Reply \"yes\" to confirm and submit.',
            $lead->name ?? '-',
            $lead->service_type ?? '-',
            $lead->location ?? '-',
            $lead->timeline ?? '-',
            $contact ?? '-',
        );
    }

    /**
     * @param array<string,mixed> $packageDraft
     * @param array<string,mixed>|null $packageQuote
     * @param array<int,string> $missingFields
     */
    private function buildPackageGuidance(array $packageDraft, ?array $packageQuote, LeadProfile $lead, array $missingFields, string $language = 'en'): string
    {
        $listingType = (string) ($packageDraft['listing_type'] ?? '');
        $services = is_array($packageDraft['services'] ?? null) ? $packageDraft['services'] : [];
        $isFrench = $language === 'fr';

        if ($listingType === '') {
            return $isFrench
                ? 'Parfait, je peux vous aider a creer votre forfait sur mesure. Quel type de propriete: maison, condo, location, chalet, ou autre?'
                : 'Great, I can help build your custom package. What listing type is this: home, condo, rental, chalet, or other?';
        }

        if ($services === []) {
            return $isFrench
                ? 'Parfait. Quels services souhaitez-vous dans votre forfait: photo, video, drone, plan d etage, ou reseaux sociaux? Vous pouvez choisir plusieurs options.'
                : 'Perfect. Which services do you want in your package: photo, video, drone, floor plan, or social media? You can choose multiple.';
        }

        if (in_array('photo', $services, true) && blank($packageDraft['photo_count'] ?? null)) {
            return $isFrench
                ? 'Pour les photos, quelle plage vous convient: jusqu a 20, 21-30, 31-45, ou 46+ photos?'
                : 'For photos, which range do you need: up to 20, 21-30, 31-45, or 46+ photos?';
        }

        if (in_array('video', $services, true) && blank($packageDraft['video_type'] ?? null)) {
            return $isFrench
                ? 'Pour la video, quel style preferez-vous: walkthrough, cinematic, ou reel?'
                : 'For video, which style do you prefer: walkthrough, cinematic, or reel?';
        }

        if (in_array('drone', $services, true) && blank($packageDraft['drone_mode'] ?? null)) {
            return $isFrench
                ? 'Pour le drone, souhaitez-vous photo drone, video drone, ou les deux?'
                : 'For drone, do you want drone photo, drone video, or both?';
        }

        if ($packageQuote !== null) {
            $summary = sprintf(
                $isFrench
                    ? 'Brouillon forfait sur mesure: propriete %s, services %s. Total estime: %d %s.'
                    : 'Custom package draft: listing %s, services %s. Estimated total: %d %s.',
                $listingType,
                implode(', ', $services),
                (int) ($packageQuote['total'] ?? 0),
                (string) ($packageQuote['currency'] ?? 'USD'),
            );

            if ($missingFields !== []) {
                $nextField = $missingFields[0];
                $nextQuestion = $this->followUpQuestion($nextField, $lead, $language);
                return $summary . ' ' . $nextQuestion;
            }

            return $summary . ($isFrench
                ? ' Si cela vous convient, repondez "yes" pour soumettre votre demande et notre equipe fera le suivi.'
                : ' If this looks good, reply "yes" to submit your request and our team will follow up.');
        }

        return $isFrench
            ? 'Je prepare votre forfait sur mesure. Pouvez-vous partager un detail de plus pour finaliser l estimation?'
            : 'I am preparing your custom package. Could you share one more detail so I can finalize the estimate?';
    }

    private function isConfirmation(string $content): bool
    {
        $clean = mb_strtolower(trim($content));
        return in_array($clean, ['yes', 'confirm', 'confirmed', 'go ahead', 'submit'], true);
    }

    /**
     * @param array<string,mixed> $draft
     * @return array<string,mixed>|null
     */
    private function calculatePackagePreview(array $draft): ?array
    {
        $listingType = (string) ($draft['listing_type'] ?? '');
        $services = is_array($draft['services'] ?? null) ? $draft['services'] : [];
        if ($listingType === '' || $services === []) {
            return null;
        }

        $payload = [
            'listing_type' => $listingType,
            'services' => $services,
        ];

        if (!blank($draft['photo_count'] ?? null)) {
            $payload['photo_count'] = (string) $draft['photo_count'];
        }
        if (!blank($draft['video_type'] ?? null)) {
            $payload['video_type'] = (string) $draft['video_type'];
        }
        if (!blank($draft['drone_mode'] ?? null)) {
            $payload['drone_mode'] = (string) $draft['drone_mode'];
        }
        if (is_array($draft['add_ons'] ?? null)) {
            $payload['add_ons'] = $draft['add_ons'];
        }

        return $this->pricingService->calculate($payload);
    }

    /**
     * @param array<string,mixed> $existing
     * @param array<string,mixed> $incoming
     * @return array<string,mixed>
     */
    private function mergePackageDraft(array $existing, array $incoming): array
    {
        $draft = $existing;
        foreach (['listing_type', 'photo_count', 'video_type', 'drone_mode'] as $field) {
            if (!blank($incoming[$field] ?? null)) {
                $draft[$field] = $incoming[$field];
            }
        }

        $existingServices = is_array($draft['services'] ?? null) ? $draft['services'] : [];
        $incomingServices = is_array($incoming['services'] ?? null) ? $incoming['services'] : [];
        $draft['services'] = array_values(array_unique(array_merge($existingServices, $incomingServices)));

        $existingAddOns = is_array($draft['add_ons'] ?? null) ? $draft['add_ons'] : [];
        $incomingAddOns = is_array($incoming['add_ons'] ?? null) ? $incoming['add_ons'] : [];
        $draft['add_ons'] = array_merge($existingAddOns, $incomingAddOns);

        return $draft;
    }

    /**
     * @return array<string,mixed>
     */
    private function extractPackageDraftFromMessage(string $content): array
    {
        $text = trim($content);
        $lower = mb_strtolower($text);
        $draft = [
            'services' => [],
            'add_ons' => [],
        ];

        foreach ([
            'home' => ['home', 'house'],
            'condo' => ['condo', 'apartment'],
            'rental' => ['rental', 'rent'],
            'chalet' => ['chalet'],
            'other' => ['other'],
        ] as $key => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $draft['listing_type'] = $key;
                    break 2;
                }
            }
        }

        $serviceKeywords = [
            'photo' => ['photo', 'photos', 'photography', 'hdr'],
            'video' => ['video', 'walkthrough', 'reel', 'cinematic'],
            'drone' => ['drone', 'aerial'],
            'floor_plan' => ['floor plan', 'floorplan'],
            'social_media' => ['social media', 'instagram', 'reel cut'],
        ];
        foreach ($serviceKeywords as $service => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $draft['services'][] = $service;
                    break;
                }
            }
        }

        if (preg_match('/\b([1-9][0-9]?)\s*(?:photos|photo|images|image)\b/i', $text, $match) === 1) {
            $count = (int) $match[1];
            $draft['photo_count'] = match (true) {
                $count <= 20 => 'up_to_20',
                $count <= 30 => '21_30',
                $count <= 45 => '31_45',
                default => '46_plus',
            };
        } elseif (str_contains($lower, 'up to 20')) {
            $draft['photo_count'] = 'up_to_20';
        } elseif (str_contains($lower, '21') || str_contains($lower, '30 photos')) {
            $draft['photo_count'] = '21_30';
        } elseif (str_contains($lower, '31') || str_contains($lower, '45 photos')) {
            $draft['photo_count'] = '31_45';
        } elseif (str_contains($lower, '46+') || str_contains($lower, 'more than 45')) {
            $draft['photo_count'] = '46_plus';
        }

        if (str_contains($lower, 'cinematic')) {
            $draft['video_type'] = 'cinematic';
        } elseif (str_contains($lower, 'reel')) {
            $draft['video_type'] = 'reel';
        } elseif (str_contains($lower, 'walkthrough') || str_contains($lower, 'walk through')) {
            $draft['video_type'] = 'walkthrough';
        }

        if (str_contains($lower, 'drone photo') && str_contains($lower, 'drone video')) {
            $draft['drone_mode'] = 'both';
        } elseif (str_contains($lower, 'drone video')) {
            $draft['drone_mode'] = 'video';
        } elseif (str_contains($lower, 'drone photo')) {
            $draft['drone_mode'] = 'photo';
        } elseif (str_contains($lower, 'both')) {
            $draft['drone_mode'] = 'both';
        }

        foreach ([
            'virtual_staging' => ['virtual staging'],
            'day_to_dusk' => ['day to dusk', 'day-to-dusk'],
            'priority_editing' => ['priority editing', 'fast editing', 'rush edit'],
        ] as $addOn => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $draft['add_ons'][$addOn] = true;
                    break;
                }
            }
        }

        return $draft;
    }

    /**
     * @param array<string,mixed> $existingPackageDraft
     */
    private function hasPackageIntent(string $content, array $existingPackageDraft): bool
    {
        if ($existingPackageDraft !== []) {
            return true;
        }

        $lower = mb_strtolower($content);
        $keywords = [
            'package', 'forfait', 'quote', 'estimate', 'pricing', 'price',
            'photo', 'video', 'drone', 'floor plan', 'social media',
            'custom plan', 'build my package',
        ];
        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function resolveAssistantLanguage(Conversation $conversation, ?string $preferredLanguage, string $content): string
    {
        $preferredLanguage = strtolower(trim((string) $preferredLanguage));
        if (in_array($preferredLanguage, ['en', 'fr'], true)) {
            return $preferredLanguage;
        }

        $metadataLanguage = strtolower(trim((string) data_get($conversation->metadata, 'language', '')));
        if (in_array($metadataLanguage, ['en', 'fr'], true)) {
            return $metadataLanguage;
        }

        $lower = mb_strtolower($content);
        $frenchMarkers = ['bonjour', 'salut', 'forfait', 'prix', 'reservation', 'propriete', 'besoin', 'merci'];
        foreach ($frenchMarkers as $marker) {
            if (str_contains($lower, $marker)) {
                return 'fr';
            }
        }

        return 'en';
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildAssistantPrompt(
        Conversation $conversation,
        string $latestUserMessage,
        LeadProfile $lead,
        array $missingFields,
        string $fallbackAssistantText,
        array $packageDraft = [],
        ?array $packageQuote = null,
        string $assistantLanguage = 'en',
    ): array {
        $recent = $conversation->messages()
            ->latest('id')
            ->limit(8)
            ->get(['role', 'content'])
            ->reverse()
            ->values();

        $historyLines = [];
        foreach ($recent as $message) {
            $historyLines[] = strtoupper((string) $message->role) . ': ' . trim((string) $message->content);
        }

        $leadState = [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'service_type' => $lead->service_type,
            'property_type' => $lead->property_type,
            'location' => $lead->location,
            'timeline' => $lead->timeline,
            'budget_min' => $lead->budget_min,
            'budget_max' => $lead->budget_max,
            'preferred_contact' => $lead->preferred_contact,
            'status' => $lead->status,
            'score' => $lead->score,
        ];

        $systemPrompt = implode("\n\n", [
            'You are Maccento AI assistant for website visitors.',
            'You must answer website/business questions accurately using the knowledge context.',
            'You must also capture lead details naturally for booking.',
            'Do not ask for data that is already captured in lead state.',
            'Ask at most one follow-up question per reply.',
            'Be concise, helpful, and professional, with premium sales tone.',
            'If user writes in French, respond fully in French. If user writes in English, respond in English.',
            'If user switches language, follow immediately.',
            'Use consultative sales style: clarify need, recommend best-fit package, optionally suggest one upgrade.',
            'Handle objections empathetically (price, speed, uncertainty, existing provider) using scripts in knowledge context.',
            'Never be pushy. Keep upsell to one primary and one optional recommendation maximum.',
            'If user asks general chat unrelated to website, answer briefly then guide back to website service support.',
            'When user asks to build a package, guide step-by-step: listing type -> services -> quantities/options -> add-ons -> estimate -> confirmation.',
            'For package help, use pricing logic context and summarize chosen options clearly.',
            'When all required fields are present, return a clear confirmation summary and ask user to reply "yes" to submit.',
            'Preferred language for this conversation: ' . $assistantLanguage . '. Keep your whole response in this language unless user asks otherwise.',
            'Knowledge context:' . "\n" . $this->knowledgeService->contextText(),
        ]);

        $userPrompt = implode("\n", [
            'Latest user message: ' . $latestUserMessage,
            'Lead state: ' . json_encode($leadState),
            'Missing required fields: ' . json_encode(array_values($missingFields)),
            'Package draft state: ' . json_encode($packageDraft),
            'Package quote preview: ' . json_encode($packageQuote),
            'Recent conversation:',
            implode("\n", $historyLines),
            'Fallback response if needed: ' . $fallbackAssistantText,
            'Generate the next assistant reply text only.',
        ]);

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];
    }
}
