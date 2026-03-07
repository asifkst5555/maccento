<?php

namespace App\Services;

use App\Mail\BrandedNotificationMail;
use App\Models\ClientProject;
use App\Models\QuoteBuild;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuoteNotificationService
{
    public function sendSubmissionEmails(QuoteBuild $quoteBuild): void
    {
        $services = is_array($quoteBuild->services) ? implode(', ', $quoteBuild->services) : (string) $quoteBuild->services;
        $lineItems = is_array($quoteBuild->line_items)
            ? collect($quoteBuild->line_items)->map(function (array $item) use ($quoteBuild): string {
                return '- ' . ($item['label'] ?? 'Item') . ': ' . number_format((int) ($item['amount'] ?? 0)) . ' ' . $quoteBuild->currency;
            })->implode("\n")
            : '';

        $clientEmail = (string) data_get($quoteBuild->options, 'contact_email', '');
        $clientName = (string) data_get($quoteBuild->options, 'contact_name', 'Client');
        $adminEmail = (string) env('QUOTE_ADMIN_EMAIL', (string) config('mail.from.address'));
        $threadProjectId = ClientProject::query()->where('quote_build_id', $quoteBuild->id)->value('id');
        $clientSubject = $this->appendProjectThreadTag("Maccento Quote {$quoteBuild->quote_id}", $threadProjectId ? (int) $threadProjectId : null);
        $adminSubject = $this->appendProjectThreadTag("New Quote Submission {$quoteBuild->quote_id}", $threadProjectId ? (int) $threadProjectId : null);

        try {
            if ($clientEmail !== '') {
                $clientBody = implode("\n", [
                    "Hello {$clientName},",
                    '',
                    "Thanks for your package request at Maccento. Your quote ID is {$quoteBuild->quote_id}.",
                    "Estimated total: " . number_format($quoteBuild->estimated_total) . " {$quoteBuild->currency}",
                    "Services: {$services}",
                    '',
                    'Selected line items:',
                    $lineItems !== '' ? $lineItems : '- N/A',
                    '',
                    'Our team will follow up shortly.',
                ]);

                Mail::to($clientEmail)->send(new BrandedNotificationMail(
                    subjectLine: $clientSubject,
                    heading: 'Your Quote Request Was Received',
                    bodyLines: $this->bodyToLines($clientBody),
                    intro: 'Thank you for choosing Maccento. Your quote details are below.',
                    ctaLabel: null,
                    ctaUrl: null,
                    footerNote: 'If you need updates, simply reply to this email and our team will help.',
                    threadProjectId: $threadProjectId ? (int) $threadProjectId : null,
                ));
            }

            if ($adminEmail !== '') {
                $adminBody = implode("\n", [
                    'New package builder submission received.',
                    "Quote ID: {$quoteBuild->quote_id}",
                    "Client: {$clientName}",
                    "Email: " . ($clientEmail !== '' ? $clientEmail : '-'),
                    "Phone: " . ((string) data_get($quoteBuild->options, 'contact_phone', '-') ?: '-'),
                    "Listing Type: " . ucfirst((string) ($quoteBuild->listing_type ?? '-')),
                    "Services: {$services}",
                    "Estimated total: " . number_format($quoteBuild->estimated_total) . " {$quoteBuild->currency}",
                    '',
                    'Line items:',
                    $lineItems !== '' ? $lineItems : '- N/A',
                ]);

                Mail::to($adminEmail)->send(new BrandedNotificationMail(
                    subjectLine: $adminSubject,
                    heading: 'New Quote Submission',
                    bodyLines: $this->bodyToLines($adminBody),
                    intro: 'A new package builder submission has been received in CRM.',
                    ctaLabel: 'Open Email Center',
                    ctaUrl: route('admin.emails.index'),
                    footerNote: 'This is an automated CRM notification.',
                    threadProjectId: $threadProjectId ? (int) $threadProjectId : null,
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Quote email notification failed', [
                'quote_id' => $quoteBuild->quote_id,
                'error' => $e->getMessage(),
            ]);
        }
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

    private function appendProjectThreadTag(string $subject, ?int $projectId): string
    {
        $trimmed = trim($subject);
        if ($trimmed === '' || $projectId === null || $projectId <= 0) {
            return $trimmed;
        }

        if (preg_match('/\[(?:project|proj|p)\s*[-:#]?\s*\d+\]/i', $trimmed) === 1) {
            return $trimmed;
        }

        return $trimmed . " [P#{$projectId}]";
    }
}
