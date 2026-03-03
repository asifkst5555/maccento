<?php

namespace App\Services;

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

                Mail::raw($clientBody, static function ($message) use ($clientEmail, $quoteBuild): void {
                    $message->to($clientEmail)->subject("Maccento Quote {$quoteBuild->quote_id}");
                });
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

                Mail::raw($adminBody, static function ($message) use ($adminEmail, $quoteBuild): void {
                    $message->to($adminEmail)->subject("New Quote Submission {$quoteBuild->quote_id}");
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Quote email notification failed', [
                'quote_id' => $quoteBuild->quote_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
