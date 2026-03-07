<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use App\Models\LeadEvent;
use App\Models\LeadProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BackfillWelcomeEmailLogs extends Command
{
    protected $signature = 'leads:backfill-welcome-email-logs {--dry-run : Show what would be inserted without writing changes}';

    protected $description = 'Backfill EmailLog entries for historical welcome_email_sent lead events so they appear in Sent tab.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $stats = [
            'events' => 0,
            'missing_lead' => 0,
            'missing_email' => 0,
            'already_logged' => 0,
            'inserted' => 0,
            'failed' => 0,
        ];

        $this->line($isDryRun
            ? 'Dry run: no rows will be inserted.'
            : 'Running backfill for welcome email logs.');

        LeadEvent::query()
            ->where('event_type', 'welcome_email_sent')
            ->orderBy('id')
            ->chunkById(300, function ($events) use (&$stats, $isDryRun): void {
                foreach ($events as $event) {
                    $stats['events']++;

                    $lead = LeadProfile::query()->find((int) $event->lead_profile_id);
                    if (!$lead) {
                        $stats['missing_lead']++;
                        continue;
                    }

                    $recipient = Str::lower(trim((string) ($lead->email ?? '')));
                    if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                        $stats['missing_email']++;
                        continue;
                    }

                    $payload = is_array($event->payload) ? $event->payload : [];
                    $source = trim((string) ($payload['source'] ?? 'unknown_source'));
                    $subject = trim((string) ($payload['subject'] ?? 'Welcome to Maccento'));
                    if ($subject === '') {
                        $subject = 'Welcome to Maccento';
                    }

                    $sentAt = $event->created_at instanceof Carbon ? $event->created_at->copy() : now();
                    $windowStart = $sentAt->copy()->subMinutes(2);
                    $windowEnd = $sentAt->copy()->addMinutes(2);

                    $alreadyLogged = EmailLog::query()
                        ->where('mode', 'auto_welcome')
                        ->whereRaw('LOWER(recipient_email) = ?', [$recipient])
                        ->where('template_key', 'lead_auto_' . Str::lower($source))
                        ->where('subject', $subject)
                        ->whereBetween('sent_at', [$windowStart, $windowEnd])
                        ->exists();

                    if ($alreadyLogged) {
                        $stats['already_logged']++;
                        continue;
                    }

                    if ($isDryRun) {
                        $stats['inserted']++;
                        continue;
                    }

                    try {
                        EmailLog::query()->create([
                            'created_by' => null,
                            'mode' => 'auto_welcome',
                            'template_key' => 'lead_auto_' . Str::lower($source),
                            'recipient_email' => $recipient,
                            'reply_to' => trim((string) env('SENDGRID_INBOUND_REPLY_TO', (string) config('mail.from.address'))) ?: null,
                            'subject' => $subject,
                            'body_preview' => null,
                            'status' => 'sent',
                            'error_message' => null,
                            'sent_at' => $sentAt,
                            'provider_message_id' => null,
                            'provider_status' => 'processed',
                            'provider_last_event_at' => $sentAt,
                        ]);

                        $stats['inserted']++;
                    } catch (\Throwable $exception) {
                        report($exception);
                        $stats['failed']++;
                    }
                }
            });

        $this->newLine();
        $this->info('Welcome EmailLog Backfill Summary');
        $this->line('Events scanned: ' . number_format($stats['events']));
        $this->line('Missing lead: ' . number_format($stats['missing_lead']));
        $this->line('Missing/invalid lead email: ' . number_format($stats['missing_email']));
        $this->line('Already logged: ' . number_format($stats['already_logged']));
        $this->line(($isDryRun ? 'Would insert: ' : 'Inserted: ') . number_format($stats['inserted']));
        $this->line('Failed inserts: ' . number_format($stats['failed']));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
