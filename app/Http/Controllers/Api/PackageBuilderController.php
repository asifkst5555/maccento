<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\LeadEvent;
use App\Models\QuoteBuild;
use App\Models\QuoteEvent;
use App\Services\PackageBuilderPricingService;
use App\Services\PanelNotificationService;
use App\Services\QuoteNotificationService;
use App\Services\LeadAutoCaptureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageBuilderController extends Controller
{
    public function __construct(
        private readonly PackageBuilderPricingService $pricingService,
        private readonly QuoteNotificationService $quoteNotificationService,
        private readonly PanelNotificationService $panelNotificationService,
        private readonly LeadAutoCaptureService $leadAutoCaptureService,
    ) {
    }

    public function calculate(Request $request): JsonResponse
    {
        $packageCode = strtolower(trim((string) $request->input('package_code', 'custom')));
        $presetQuote = $this->presetQuote($packageCode);
        if ($presetQuote !== null) {
            return response()->json($presetQuote);
        }

        $validated = $request->validate([
            'listing_type' => ['required', 'in:home,condo,rental,chalet,other'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['string', 'in:photo,video,drone,floor_plan,social_media'],
            'photo_count' => ['nullable', 'in:up_to_20,21_30,31_45,46_plus'],
            'video_type' => ['nullable', 'in:walkthrough,cinematic,reel'],
            'drone_mode' => ['nullable', 'in:photo,video,both'],
            'add_ons' => ['nullable', 'array'],
            'add_ons.virtual_staging' => ['nullable', 'boolean'],
            'add_ons.day_to_dusk' => ['nullable', 'boolean'],
            'add_ons.priority_editing' => ['nullable', 'boolean'],
        ]);

        $quote = $this->pricingService->calculate($validated);

        return response()->json($quote);
    }

    public function submit(Request $request): JsonResponse
    {
        $packageCode = strtolower(trim((string) $request->input('package_code', 'custom')));
        $presetQuote = $this->presetQuote($packageCode);

        if ($presetQuote !== null) {
            $validated = $request->validate([
                'visitor_id' => ['nullable', 'string', 'max:100'],
                'contact_name' => ['required', 'string', 'max:120'],
                'contact_email' => ['nullable', 'email', 'max:255'],
                'contact_phone' => ['nullable', 'string', 'max:30'],
                'message' => ['nullable', 'string', 'max:1000'],
                'language' => ['nullable', 'in:en,fr'],
            ]);

            if (blank($validated['contact_email'] ?? null) && blank($validated['contact_phone'] ?? null)) {
                return response()->json([
                    'message' => 'Please provide at least email or phone.',
                ], 422);
            }

            $listingType = (string) ($presetQuote['listing_type'] ?? 'other');
            $services = is_array($presetQuote['services'] ?? null) ? $presetQuote['services'] : [];

            $conversation = Conversation::create([
                'channel' => 'package_builder',
                'visitor_id' => $validated['visitor_id'] ?? null,
                'status' => 'active',
                'started_at' => now(),
                'last_message_at' => now(),
                'metadata' => [
                    'ip' => $request->ip(),
                    'language' => $validated['language'] ?? 'en',
                    'package_code' => $packageCode,
                ],
            ]);

            $lead = $this->leadAutoCaptureService->captureAndWelcome([
                'name' => $validated['contact_name'],
                'email' => $validated['contact_email'] ?? null,
                'phone' => $validated['contact_phone'] ?? null,
                'service_type' => implode(',', $services),
                'property_type' => $listingType,
                'notes' => $validated['message'] ?? null,
                'score' => 55,
                'status' => 'new',
            ], 'website_packages', $conversation);

            $conversation->messages()->create([
                'role' => 'user',
                'content' => 'Package preset submission: ' . json_encode([
                    'package_code' => $packageCode,
                    'listing_type' => $listingType,
                    'services' => $services,
                    'estimated_total' => $presetQuote['total'] ?? 0,
                    'display_total' => $presetQuote['display_total'] ?? null,
                ]),
                'metadata' => ['type' => 'package_builder_preset_submit'],
            ]);

            $quoteBuild = QuoteBuild::create([
                'quote_id' => QuoteBuild::makeQuoteId(),
                'user_id' => $request->user()?->id,
                'conversation_id' => $conversation->id,
                'lead_profile_id' => $lead?->id,
                'visitor_id' => $validated['visitor_id'] ?? null,
                'status' => 'new',
                'listing_type' => $listingType,
                'services' => $services,
                'options' => [
                    'package_code' => $packageCode,
                    'package_title' => $presetQuote['package_title'] ?? ucfirst($packageCode),
                    'display_total' => $presetQuote['display_total'] ?? null,
                    'contact_name' => $validated['contact_name'],
                    'contact_email' => $validated['contact_email'] ?? null,
                    'contact_phone' => $validated['contact_phone'] ?? null,
                    'language' => $validated['language'] ?? 'en',
                ],
                'line_items' => $presetQuote['line_items'] ?? [],
                'estimated_total' => (int) ($presetQuote['total'] ?? 0),
                'currency' => (string) ($presetQuote['currency'] ?? 'USD'),
                'notes' => $validated['message'] ?? null,
                'submitted_at' => now(),
            ]);

            QuoteEvent::create([
                'quote_build_id' => $quoteBuild->id,
                'event_type' => 'submitted',
                'payload' => [
                    'estimated_total' => $quoteBuild->estimated_total,
                    'package_code' => $packageCode,
                ],
                'created_by' => $request->user()?->id,
            ]);

            if ($lead) {
                LeadEvent::create([
                    'lead_profile_id' => $lead->id,
                    'event_type' => 'package_builder_submitted',
                    'payload' => [
                        'quote_id' => $quoteBuild->quote_id,
                        'estimated_total' => $quoteBuild->estimated_total,
                        'currency' => $quoteBuild->currency,
                        'package_code' => $packageCode,
                    ],
                    'created_by' => $request->user()?->id,
                ]);
            }

            $this->quoteNotificationService->sendSubmissionEmails($quoteBuild);
            $this->panelNotificationService->notifyInternal(
                'new_quote_submission',
                'New package request submitted',
                "Quote {$quoteBuild->quote_id} submitted ({$quoteBuild->estimated_total} {$quoteBuild->currency}).",
                route('admin.quotes.show', $quoteBuild),
                ['quote_id' => $quoteBuild->id]
            );

            return response()->json([
                'quote_id' => $quoteBuild->quote_id,
                'estimated_total' => $quoteBuild->estimated_total,
                'display_total' => $presetQuote['display_total'] ?? null,
                'currency' => $quoteBuild->currency,
                'status' => 'submitted',
            ], 201);
        }

        $validated = $request->validate([
            'visitor_id' => ['nullable', 'string', 'max:100'],
            'package_code' => ['nullable', 'in:custom'],
            'listing_type' => ['required', 'in:home,condo,rental,chalet,other'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['string', 'in:photo,video,drone,floor_plan,social_media'],
            'photo_count' => ['nullable', 'in:up_to_20,21_30,31_45,46_plus'],
            'video_type' => ['nullable', 'in:walkthrough,cinematic,reel'],
            'drone_mode' => ['nullable', 'in:photo,video,both'],
            'add_ons' => ['nullable', 'array'],
            'add_ons.virtual_staging' => ['nullable', 'boolean'],
            'add_ons.day_to_dusk' => ['nullable', 'boolean'],
            'add_ons.priority_editing' => ['nullable', 'boolean'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:1000'],
            'language' => ['nullable', 'in:en,fr'],
        ]);

        if (blank($validated['contact_email'] ?? null) && blank($validated['contact_phone'] ?? null)) {
            return response()->json([
                'message' => 'Please provide at least email or phone.',
            ], 422);
        }

        $quote = $this->pricingService->calculate($validated);

        $conversation = Conversation::create([
            'channel' => 'package_builder',
            'visitor_id' => $validated['visitor_id'] ?? null,
            'status' => 'active',
            'started_at' => now(),
            'last_message_at' => now(),
            'metadata' => [
                'ip' => $request->ip(),
                'language' => $validated['language'] ?? 'en',
            ],
        ]);

        $lead = $this->leadAutoCaptureService->captureAndWelcome([
            'name' => $validated['contact_name'],
            'email' => $validated['contact_email'] ?? null,
            'phone' => $validated['contact_phone'] ?? null,
            'service_type' => implode(',', $validated['services']),
            'property_type' => $validated['listing_type'],
            'notes' => $validated['message'] ?? null,
            'score' => 55,
            'status' => 'new',
        ], 'website_packages', $conversation);

        $conversation->messages()->create([
            'role' => 'user',
            'content' => 'Package builder submission: ' . json_encode([
                'listing_type' => $validated['listing_type'],
                'services' => $validated['services'],
                'options' => [
                    'photo_count' => $validated['photo_count'] ?? null,
                    'video_type' => $validated['video_type'] ?? null,
                    'drone_mode' => $validated['drone_mode'] ?? null,
                    'add_ons' => $validated['add_ons'] ?? [],
                ],
                'estimated_total' => $quote['total'],
                'currency' => $quote['currency'],
            ]),
            'metadata' => ['type' => 'package_builder_submit'],
        ]);

        $quoteBuild = QuoteBuild::create([
            'quote_id' => QuoteBuild::makeQuoteId(),
            'user_id' => $request->user()?->id,
            'conversation_id' => $conversation->id,
            'lead_profile_id' => $lead?->id,
            'visitor_id' => $validated['visitor_id'] ?? null,
            'status' => 'new',
            'listing_type' => $validated['listing_type'],
            'services' => $validated['services'],
            'options' => [
                'package_code' => 'custom',
                'photo_count' => $validated['photo_count'] ?? null,
                'video_type' => $validated['video_type'] ?? null,
                'drone_mode' => $validated['drone_mode'] ?? null,
                'add_ons' => $validated['add_ons'] ?? [],
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'language' => $validated['language'] ?? 'en',
            ],
            'line_items' => $quote['line_items'],
            'estimated_total' => $quote['total'],
            'currency' => $quote['currency'],
            'notes' => $validated['message'] ?? null,
            'submitted_at' => now(),
        ]);

        QuoteEvent::create([
            'quote_build_id' => $quoteBuild->id,
            'event_type' => 'submitted',
            'payload' => ['estimated_total' => $quoteBuild->estimated_total],
            'created_by' => $request->user()?->id,
        ]);

        if ($lead) {
            LeadEvent::create([
                'lead_profile_id' => $lead->id,
                'event_type' => 'package_builder_submitted',
                'payload' => [
                    'quote_id' => $quoteBuild->quote_id,
                    'estimated_total' => $quoteBuild->estimated_total,
                    'currency' => $quoteBuild->currency,
                ],
                'created_by' => $request->user()?->id,
            ]);
        }

        $this->quoteNotificationService->sendSubmissionEmails($quoteBuild);
        $this->panelNotificationService->notifyInternal(
            'new_quote_submission',
            'New package request submitted',
            "Quote {$quoteBuild->quote_id} submitted ({$quoteBuild->estimated_total} {$quoteBuild->currency}).",
            route('admin.quotes.show', $quoteBuild),
            ['quote_id' => $quoteBuild->id]
        );

        return response()->json([
            'quote_id' => $quoteBuild->quote_id,
            'estimated_total' => $quoteBuild->estimated_total,
            'currency' => $quoteBuild->currency,
            'status' => 'submitted',
        ], 201);
    }

    private function presetQuote(string $packageCode): ?array
    {
        if (!in_array($packageCode, ['essential', 'signature', 'prestige'], true)) {
            return null;
        }

        $preset = config("package_builder.package_presets.{$packageCode}");
        if (!is_array($preset)) {
            return null;
        }

        return [
            'package_code' => $packageCode,
            'package_title' => (string) ($preset['title'] ?? ucfirst($packageCode)),
            'currency' => (string) config('package_builder.currency', 'USD'),
            'display_total' => (string) ($preset['display_total'] ?? ''),
            'total' => (int) ($preset['estimated_total'] ?? 0),
            'listing_type' => (string) ($preset['listing_type'] ?? 'other'),
            'services' => is_array($preset['services'] ?? null) ? $preset['services'] : [],
            'line_items' => is_array($preset['line_items'] ?? null) ? $preset['line_items'] : [],
            'notes' => is_array($preset['notes'] ?? null) ? $preset['notes'] : [],
        ];
    }
}
