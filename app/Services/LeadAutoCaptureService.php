<?php

namespace App\Services;

use App\Mail\BrandedNotificationMail;
use App\Models\Conversation;
use App\Models\EmailLog;
use App\Models\LeadAutoEmailSetting;
use App\Models\LeadEvent;
use App\Models\LeadProfile;
use App\Services\AI\AiProviderManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class LeadAutoCaptureService
{
    public function __construct(
        private readonly AiProviderManager $aiProviderManager,
    ) {
    }

    /**
     * Capture/update a lead and send one auto welcome email per source.
     * Lead is only persisted when email is present.
     *
     * @param array<string,mixed> $attributes
     */
    public function captureAndWelcome(array $attributes, string $source, ?Conversation $conversation = null): ?LeadProfile
    {
        $email = Str::lower(trim((string) ($attributes['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $source = trim($source);
        if ($source === '') {
            $source = 'unknown_source';
        }

        $lead = LeadProfile::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->latest('id')
            ->first();

        if (!$lead) {
            $lead = new LeadProfile();
            $lead->status = 'new';
            $lead->score = 0;
            $lead->email = $email;
        }

        $lead->email = $email;

        if ($conversation && blank($lead->conversation_id)) {
            $lead->conversation_id = $conversation->id;
        }

        $this->mergeFieldIfEmpty($lead, 'name', $attributes['name'] ?? null);
        $this->mergeFieldIfEmpty($lead, 'phone', $attributes['phone'] ?? null);
        $this->mergeFieldIfEmpty($lead, 'service_type', $attributes['service_type'] ?? null);
        $this->mergeFieldIfEmpty($lead, 'property_type', $attributes['property_type'] ?? null);
        $this->mergeFieldIfEmpty($lead, 'location', $attributes['location'] ?? null);
        $this->mergeFieldIfEmpty($lead, 'timeline', $attributes['timeline'] ?? null);

        if (isset($attributes['score'])) {
            $lead->score = max((int) ($lead->score ?? 0), (int) $attributes['score']);
        }

        if (blank($lead->status)) {
            $lead->status = (string) ($attributes['status'] ?? 'new');
        }

        if (!blank($attributes['notes'] ?? null)) {
            $note = trim((string) $attributes['notes']);
            if ($note !== '') {
                $existing = (string) ($lead->notes ?? '');
                $prefix = "[{$source}] ";
                if (!str_contains($existing, $prefix . $note)) {
                    $lead->notes = trim($existing . ($existing !== '' ? "\n" : '') . $prefix . $note);
                }
            }
        }

        $lead->save();

        if (!$this->hasEvent($lead->id, 'lead_captured', $source)) {
            LeadEvent::create([
                'lead_profile_id' => $lead->id,
                'event_type' => 'lead_captured',
                'payload' => [
                    'source' => $source,
                    'email' => $lead->email,
                    'conversation_id' => $conversation?->id,
                ],
                'created_by' => null,
            ]);
        }

        $this->sendWelcomeEmailIfNeeded($lead, $source, $attributes);

        return $lead->fresh();
    }

    /**
     * @param mixed $value
     */
    private function mergeFieldIfEmpty(LeadProfile $lead, string $field, $value): void
    {
        $normalized = is_string($value) ? trim($value) : $value;
        if (blank($normalized)) {
            return;
        }

        if (blank($lead->{$field})) {
            $lead->{$field} = $normalized;
        }
    }

    private function hasEvent(int $leadId, string $eventType, string $source): bool
    {
        return LeadEvent::query()
            ->where('lead_profile_id', $leadId)
            ->where('event_type', $eventType)
            ->where('payload->source', $source)
            ->exists();
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function sendWelcomeEmailIfNeeded(LeadProfile $lead, string $source, array $attributes): void
    {
        if (blank($lead->email)) {
            return;
        }

        $settings = $this->resolveSourceSettings($source);
        if (!(bool) ($settings['enabled'] ?? true)) {
            return;
        }

        if ($this->hasEvent((int) $lead->id, 'welcome_email_sent', $source)) {
            return;
        }

        $subject = 'Welcome to Maccento';
        $message = $this->fallbackMessage($lead, $source, $attributes);
        $providerName = 'fallback';
        $modelName = 'n/a';
        $replyTo = trim((string) env('SENDGRID_INBOUND_REPLY_TO', (string) config('mail.from.address')));
        $emailLog = null;

        try {
            $requirements = $this->buildRequirementSummary($lead, $attributes);
            $prompt = implode("\n", [
                'Write a concise, warm, professional welcome email for a new CRM lead.',
                'Return plain text in this exact format:',
                'Subject: <subject>',
                'Body:',
                '<body>',
                '',
                'Constraints:',
                '- Keep it under 140 words.',
                '- Reference the lead requirements briefly.',
                '- Include one clear next step and reply CTA.',
                '- No markdown.',
                '',
                'Lead source: ' . $source,
                'Tone: ' . (string) ($settings['tone'] ?? 'professional'),
                'Template instruction: ' . (string) ($settings['template_prompt'] ?? 'General welcome and acknowledgement.'),
                'Lead name: ' . ((string) ($lead->name ?: 'there')),
                'Requirements: ' . ($requirements !== '' ? $requirements : 'No specific requirements provided'),
            ]);

            $provider = $this->aiProviderManager->provider();
            $usage = $provider->chat([
                ['role' => 'system', 'content' => 'You are an expert client success email writer.'],
                ['role' => 'user', 'content' => $prompt],
            ]);

            $providerName = $provider->name();
            $modelName = (string) ($usage['model'] ?? '');
            $content = trim((string) ($usage['content'] ?? ''));

            if (preg_match('/^Subject:\s*(.+)$/mi', $content, $subjectMatch) === 1) {
                $subject = trim((string) ($subjectMatch[1] ?? $subject));
            }
            if (preg_match('/^Body:\s*(.*)$/mis', $content, $bodyMatch) === 1) {
                $message = trim((string) ($bodyMatch[1] ?? $message));
            } elseif ($content !== '') {
                $message = $content;
            }

            if ($subject === '') {
                $subject = 'Welcome to Maccento';
            }
            $subjectPrefix = trim((string) ($settings['subject_prefix'] ?? ''));
            if ($subjectPrefix !== '' && !str_starts_with(Str::lower($subject), Str::lower($subjectPrefix))) {
                $subject = rtrim($subjectPrefix) . ' ' . ltrim($subject);
            }
            if ($message === '') {
                $message = $this->fallbackMessage($lead, $source, $attributes);
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            $emailLog = EmailLog::query()->create([
                'created_by' => null,
                'mode' => 'auto_welcome',
                'template_key' => 'lead_auto_' . Str::lower($source),
                'recipient_email' => (string) $lead->email,
                'reply_to' => $replyTo !== '' ? $replyTo : null,
                'subject' => $subject,
                'body_preview' => Str::limit($message, 700),
                'status' => 'queued',
                'error_message' => null,
                'sent_at' => null,
                'provider_status' => 'queued',
                'provider_last_event_at' => now(),
            ]);

            Mail::to((string) $lead->email)->send(new BrandedNotificationMail(
                subjectLine: $subject,
                heading: 'Welcome to Maccento',
                bodyLines: $this->bodyToLines($message),
                intro: 'Thanks for contacting us. We received your details and your request is now in our CRM pipeline.',
                ctaLabel: 'Visit Maccento',
                ctaUrl: url('/'),
                footerNote: 'Reply to this email if you want to share more details before we contact you.',
                emailLogId: $emailLog?->id,
                replyToAddress: $replyTo !== '' ? $replyTo : null,
            ));

            if ($emailLog) {
                $emailLog->forceFill([
                    'status' => 'sent',
                    'error_message' => null,
                    'sent_at' => now(),
                    'provider_status' => 'processed',
                    'provider_last_event_at' => now(),
                ])->save();
            }

            LeadEvent::create([
                'lead_profile_id' => $lead->id,
                'event_type' => 'welcome_email_sent',
                'payload' => [
                    'source' => $source,
                    'subject' => $subject,
                    'provider' => $providerName,
                    'model' => $modelName,
                ],
                'created_by' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            if ($emailLog) {
                $emailLog->forceFill([
                    'status' => 'failed',
                    'error_message' => Str::limit($exception->getMessage(), 500),
                    'provider_status' => 'failed',
                    'provider_last_event_at' => now(),
                ])->save();
            } else {
                try {
                    EmailLog::query()->create([
                        'created_by' => null,
                        'mode' => 'auto_welcome',
                        'template_key' => 'lead_auto_' . Str::lower($source),
                        'recipient_email' => (string) $lead->email,
                        'reply_to' => $replyTo !== '' ? $replyTo : null,
                        'subject' => $subject,
                        'body_preview' => Str::limit($message, 700),
                        'status' => 'failed',
                        'error_message' => Str::limit($exception->getMessage(), 500),
                        'sent_at' => null,
                        'provider_status' => 'failed',
                        'provider_last_event_at' => now(),
                    ]);
                } catch (Throwable $innerException) {
                    report($innerException);
                }
            }

            LeadEvent::create([
                'lead_profile_id' => $lead->id,
                'event_type' => 'welcome_email_failed',
                'payload' => [
                    'source' => $source,
                    'error' => Str::limit($exception->getMessage(), 350),
                ],
                'created_by' => null,
            ]);
        }
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function buildRequirementSummary(LeadProfile $lead, array $attributes): string
    {
        $parts = [];

        $service = trim((string) ($attributes['service_type'] ?? $lead->service_type ?? ''));
        $property = trim((string) ($attributes['property_type'] ?? $lead->property_type ?? ''));
        $location = trim((string) ($attributes['location'] ?? $lead->location ?? ''));
        $timeline = trim((string) ($attributes['timeline'] ?? $lead->timeline ?? ''));
        $notes = trim((string) ($attributes['notes'] ?? ''));

        if ($service !== '') {
            $parts[] = 'service: ' . $service;
        }
        if ($property !== '') {
            $parts[] = 'property: ' . $property;
        }
        if ($location !== '') {
            $parts[] = 'location: ' . $location;
        }
        if ($timeline !== '') {
            $parts[] = 'timeline: ' . $timeline;
        }
        if ($notes !== '') {
            $parts[] = 'notes: ' . Str::limit($notes, 200);
        }

        return implode('; ', $parts);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function fallbackMessage(LeadProfile $lead, string $source, array $attributes): string
    {
        $name = trim((string) ($lead->name ?? ''));
        $service = trim((string) ($attributes['service_type'] ?? $lead->service_type ?? ''));
        $location = trim((string) ($attributes['location'] ?? $lead->location ?? ''));

        $lines = [
            $name !== '' ? "Hi {$name}," : 'Hi there,',
            '',
            'Thanks for contacting Maccento. We have received your request and added it to our CRM follow-up queue.',
        ];

        if ($service !== '') {
            $lines[] = 'Requested service: ' . $service . '.';
        }
        if ($location !== '') {
            $lines[] = 'Area: ' . $location . '.';
        }

        $lines[] = 'Our team will review your requirements and contact you shortly with the next steps.';
        $lines[] = 'If you want to add more details, simply reply to this email.';
        $lines[] = '';
        $lines[] = 'Best regards,';
        $lines[] = 'Alessio Battista';
        $lines[] = 'Maccento Real Estate Media';

        return implode("\n", $lines);
    }

    /**
     * @return array{enabled:bool,tone:string,template_prompt:string,subject_prefix:string}
     */
    public function resolveSourceSettings(string $source): array
    {
        $source = trim($source);

        $defaults = [
            'website_packages' => [
                'enabled' => true,
                'tone' => 'consultative',
                'template_prompt' => 'Mention package request, reassure fast follow-up, and ask one clarifying question.',
                'subject_prefix' => 'Maccento Package Team:',
            ],
            'website_contact_form_submission' => [
                'enabled' => true,
                'tone' => 'professional',
                'template_prompt' => 'Acknowledge contact form details and confirm an upcoming response with next steps.',
                'subject_prefix' => 'Maccento Support:',
            ],
            'website_contact_form' => [
                'enabled' => true,
                'tone' => 'professional',
                'template_prompt' => 'Acknowledge contact form details and confirm an upcoming response with next steps.',
                'subject_prefix' => 'Maccento Support:',
            ],
            'ai_chat_lead' => [
                'enabled' => true,
                'tone' => 'friendly',
                'template_prompt' => 'Reference chat discussion and summarize captured requirements briefly.',
                'subject_prefix' => 'Maccento Chat Team:',
            ],
        ];

        $fallback = $defaults[$source] ?? [
            'enabled' => true,
            'tone' => 'professional',
            'template_prompt' => 'General warm welcome and acknowledgement.',
            'subject_prefix' => 'Maccento:',
        ];

        $setting = LeadAutoEmailSetting::query()->where('source', $source)->first();
        if (!$setting) {
            return $fallback;
        }

        return [
            'enabled' => (bool) $setting->enabled,
            'tone' => trim((string) ($setting->tone ?: $fallback['tone'])),
            'template_prompt' => trim((string) ($setting->template_prompt ?: $fallback['template_prompt'])),
            'subject_prefix' => trim((string) ($setting->subject_prefix ?: $fallback['subject_prefix'])),
        ];
    }

    /**
     * @return array<int,string>
     */
    private function bodyToLines(string $body): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $body) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter(static fn (string $line): bool => $line !== '')
            ->values()
            ->all();
    }
}
