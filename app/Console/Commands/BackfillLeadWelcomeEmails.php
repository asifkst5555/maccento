<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\LeadEvent;
use App\Models\LeadProfile;
use App\Models\QuoteBuild;
use App\Models\WebsiteFormSubmission;
use App\Services\LeadAutoCaptureService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillLeadWelcomeEmails extends Command
{
    protected $signature = 'leads:backfill-welcome-emails {--dry-run : Show what would be processed without writing changes or sending emails}';

    protected $description = 'Backfill auto-captured leads and welcome emails for historical website/contact/chat records.';

    public function __construct(
        private readonly LeadAutoCaptureService $captureService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $stats = [
            'considered' => 0,
            'invalid_email' => 0,
            'deduped_in_run' => 0,
            'already_sent' => 0,
            'would_send' => 0,
            'sent_or_queued' => 0,
            'failed_or_blocked' => 0,
        ];

        $seen = [];

        $this->line($isDryRun
            ? 'Running in dry-run mode. No DB writes and no emails will be sent.'
            : 'Running backfill mode. This may send welcome emails for eligible historical leads.');

        $this->newLine();
        $this->info('Source: website_contact_form_submission');
        WebsiteFormSubmission::query()
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$stats, &$seen, $isDryRun): void {
                foreach ($rows as $row) {
                    $email = $this->normalizeEmail((string) ($row->email ?? ''));
                    $attributes = [
                        'name' => (string) ($row->name ?? ''),
                        'email' => $email,
                        'phone' => (string) ($row->phone ?? ''),
                        'service_type' => (string) ($row->service ?? ''),
                        'location' => (string) ($row->region ?? ''),
                        'notes' => (string) ($row->message ?? ''),
                    ];

                    $this->processCandidate(
                        source: 'website_contact_form_submission',
                        email: $email,
                        attributes: $attributes,
                        conversation: null,
                        stats: $stats,
                        seen: $seen,
                        isDryRun: $isDryRun,
                    );
                }
            });

        $this->newLine();
        $this->info('Source: website_packages');
        QuoteBuild::query()
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$stats, &$seen, $isDryRun): void {
                foreach ($rows as $row) {
                    $options = is_array($row->options) ? $row->options : [];
                    $services = is_array($row->services) ? implode(', ', array_filter($row->services, static fn ($value): bool => is_string($value) && trim($value) !== '')) : '';
                    $email = $this->normalizeEmail((string) ($options['contact_email'] ?? ''));

                    $attributes = [
                        'name' => (string) ($options['contact_name'] ?? ''),
                        'email' => $email,
                        'phone' => (string) ($options['contact_phone'] ?? ''),
                        'service_type' => $services,
                        'timeline' => (string) ($options['timeline'] ?? ''),
                        'notes' => (string) ($row->notes ?? ''),
                    ];

                    $this->processCandidate(
                        source: 'website_packages',
                        email: $email,
                        attributes: $attributes,
                        conversation: null,
                        stats: $stats,
                        seen: $seen,
                        isDryRun: $isDryRun,
                    );
                }
            });

        $this->newLine();
        $this->info('Source: ai_chat_lead');
        LeadProfile::query()
            ->with('conversation:id,channel')
            ->whereNotNull('conversation_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$stats, &$seen, $isDryRun): void {
                foreach ($rows as $row) {
                    $email = $this->normalizeEmail((string) ($row->email ?? ''));
                    $attributes = [
                        'name' => (string) ($row->name ?? ''),
                        'email' => $email,
                        'phone' => (string) ($row->phone ?? ''),
                        'service_type' => (string) ($row->service_type ?? ''),
                        'property_type' => (string) ($row->property_type ?? ''),
                        'location' => (string) ($row->location ?? ''),
                        'timeline' => (string) ($row->timeline ?? ''),
                        'notes' => (string) ($row->notes ?? ''),
                        'score' => (int) ($row->score ?? 0),
                    ];

                    $this->processCandidate(
                        source: 'ai_chat_lead',
                        email: $email,
                        attributes: $attributes,
                        conversation: $row->conversation,
                        stats: $stats,
                        seen: $seen,
                        isDryRun: $isDryRun,
                    );
                }
            });

        $this->newLine();
        $this->info('Backfill summary');
        $this->line('Considered: ' . number_format($stats['considered']));
        $this->line('Invalid email: ' . number_format($stats['invalid_email']));
        $this->line('Deduped in run: ' . number_format($stats['deduped_in_run']));
        $this->line('Already sent before run: ' . number_format($stats['already_sent']));
        $this->line('Would send (dry run): ' . number_format($stats['would_send']));
        $this->line('Sent/queued this run: ' . number_format($stats['sent_or_queued']));
        $this->line('Failed or blocked: ' . number_format($stats['failed_or_blocked']));

        return $stats['failed_or_blocked'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param array<string,mixed> $attributes
     * @param array<string,int> $stats
     * @param array<string,bool> $seen
     */
    private function processCandidate(
        string $source,
        string $email,
        array $attributes,
        ?Conversation $conversation,
        array &$stats,
        array &$seen,
        bool $isDryRun
    ): void {
        $stats['considered']++;

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stats['invalid_email']++;
            return;
        }

        $dedupeKey = $source . '|' . $email;
        if (isset($seen[$dedupeKey])) {
            $stats['deduped_in_run']++;
            return;
        }
        $seen[$dedupeKey] = true;

        if ($this->emailHasWelcomeEvent($email, $source)) {
            $stats['already_sent']++;
            return;
        }

        if ($isDryRun) {
            $stats['would_send']++;
            return;
        }

        $lead = $this->captureService->captureAndWelcome($attributes, $source, $conversation);
        if (!$lead) {
            $stats['failed_or_blocked']++;
            return;
        }

        if ($this->leadHasWelcomeEvent((int) $lead->id, $source)) {
            $stats['sent_or_queued']++;
            return;
        }

        $stats['failed_or_blocked']++;
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function emailHasWelcomeEvent(string $email, string $source): bool
    {
        $lead = LeadProfile::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->latest('id')
            ->first();

        if (!$lead) {
            return false;
        }

        return $this->leadHasWelcomeEvent((int) $lead->id, $source);
    }

    private function leadHasWelcomeEvent(int $leadId, string $source): bool
    {
        return LeadEvent::query()
            ->where('lead_profile_id', $leadId)
            ->where('event_type', 'welcome_email_sent')
            ->where('payload->source', $source)
            ->exists();
    }
}
