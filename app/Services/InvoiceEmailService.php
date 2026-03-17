<?php

namespace App\Services;

use App\Mail\BrandedNotificationMail;
use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class InvoiceEmailService
{
    public function sendInvoiceCreated(ClientInvoice $invoice, Client $client, ?User $actor = null): bool
    {
        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'notify_invoice_email') && $client->notify_invoice_email === false) {
            return false;
        }

        $recipient = $this->normalizeRecipient($client->email);
        if ($recipient === null) {
            return false;
        }

        $subject = 'Invoice ' . $invoice->invoice_number . ' is ready';
        $lines = array_filter([
            'Hello ' . $this->clientGreeting($client) . ',',
            'Your invoice has been issued.',
            $this->formatAmountLine($invoice),
            $this->formatDueDateLine($invoice),
            'You can review and pay the invoice in the client portal.',
        ], static fn (?string $line): bool => $line !== null && $line !== '');

        return $this->sendEmail(
            recipient: $recipient,
            subject: $subject,
            bodyLines: $lines,
            heading: 'New invoice available',
            ctaLabel: 'View invoice',
            ctaUrl: route('user.invoices.pay', [$invoice]),
            templateKey: 'invoice_created',
            createdBy: $actor?->id
        );
    }

    public function sendDueReminder(ClientInvoice $invoice, Client $client, int $daysUntilDue): bool
    {
        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'notify_invoice_email') && $client->notify_invoice_email === false) {
            return false;
        }

        $recipient = $this->normalizeRecipient($client->email);
        if ($recipient === null) {
            return false;
        }

        $dueDate = $this->formatDueDateValue($invoice);
        if ($dueDate === null) {
            return false;
        }

        $isDueToday = $daysUntilDue <= 0;
        $subject = $isDueToday
            ? 'Invoice ' . $invoice->invoice_number . ' is due today (' . $dueDate . ')'
            : 'Payment reminder: Invoice ' . $invoice->invoice_number . ' due on ' . $dueDate;
        $heading = $isDueToday ? 'Invoice due today' : 'Upcoming invoice due';
        $statusLine = $isDueToday
            ? 'This invoice is due today.'
            : 'This invoice is due in ' . $daysUntilDue . ' days.';

        $lines = array_filter([
            'Hello ' . $this->clientGreeting($client) . ',',
            $statusLine,
            $this->formatAmountLine($invoice),
            'Due date: ' . $dueDate,
            'You can review and pay the invoice in the client portal.',
        ], static fn (?string $line): bool => $line !== null && $line !== '');

        $templateKey = $isDueToday ? 'invoice_due_today' : 'invoice_due_3_days';

        return $this->sendEmail(
            recipient: $recipient,
            subject: $subject,
            bodyLines: $lines,
            heading: $heading,
            ctaLabel: 'View invoice',
            ctaUrl: route('user.invoices.pay', [$invoice]),
            templateKey: $templateKey,
            createdBy: null
        );
    }

    public function sendOverdueReminder(ClientInvoice $invoice, Client $client, int $daysPastDue): bool
    {
        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'notify_invoice_email') && $client->notify_invoice_email === false) {
            return false;
        }

        $recipient = $this->normalizeRecipient($client->email);
        if ($recipient === null) {
            return false;
        }

        $dueDate = $this->formatDueDateValue($invoice);
        if ($dueDate === null) {
            return false;
        }

        $subject = 'Overdue reminder: Invoice ' . $invoice->invoice_number
            . ' was due on ' . $dueDate . ' (' . $daysPastDue . ' days overdue)';

        $lines = array_filter([
            'Hello ' . $this->clientGreeting($client) . ',',
            'This invoice is now overdue.',
            $this->formatAmountLine($invoice),
            'Original due date: ' . $dueDate,
            'Please review and complete payment as soon as possible.',
        ], static fn (?string $line): bool => $line !== null && $line !== '');

        return $this->sendEmail(
            recipient: $recipient,
            subject: $subject,
            bodyLines: $lines,
            heading: 'Invoice overdue',
            ctaLabel: 'View invoice',
            ctaUrl: route('user.invoices.pay', [$invoice]),
            templateKey: 'invoice_overdue_3_days',
            createdBy: null
        );
    }

    /**
     * @param array<int,string> $bodyLines
     */
    private function sendEmail(
        string $recipient,
        string $subject,
        array $bodyLines,
        string $heading,
        ?string $ctaLabel,
        ?string $ctaUrl,
        string $templateKey,
        ?int $createdBy
    ): bool {
        if ($this->alreadySent($templateKey, $recipient, $subject)) {
            return false;
        }

        $replyTo = \App\Services\CrmReplyToResolver::resolve();
        $preview = Str::limit(implode(' ', $bodyLines), 700);

        $emailLog = $this->createEmailLog([
            'created_by' => $createdBy,
            'mode' => 'auto_invoice',
            'template_key' => $templateKey,
            'recipient_email' => $recipient,
            'reply_to' => $replyTo !== '' ? $replyTo : null,
            'subject' => $subject,
            'body_preview' => $preview,
            'status' => 'queued',
            'provider_status' => 'queued',
        ]);

        try {
            Mail::to($recipient)->send(new BrandedNotificationMail(
                subjectLine: $subject,
                heading: $heading,
                bodyLines: $bodyLines,
                intro: 'This is an automated invoice notification from your CRM.',
                ctaLabel: $ctaLabel,
                ctaUrl: $ctaUrl,
                footerNote: 'If you have any questions, reply to this email and our team will help.',
                emailLogId: $emailLog?->id,
                threadProjectId: null,
                replyToAddress: $replyTo !== '' ? $replyTo : null
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

            return true;
        } catch (Throwable $exception) {
            report($exception);

            if ($emailLog) {
                $emailLog->forceFill([
                    'status' => 'failed',
                    'error_message' => Str::limit($exception->getMessage(), 500),
                    'provider_status' => 'failed',
                    'provider_last_event_at' => now(),
                ])->save();
            }

            return false;
        }
    }

    private function alreadySent(string $templateKey, string $recipient, string $subject): bool
    {
        return EmailLog::query()
            ->where('template_key', $templateKey)
            ->whereRaw('LOWER(recipient_email) = ?', [strtolower($recipient)])
            ->where('subject', $subject)
            ->exists();
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function createEmailLog(array $payload): ?EmailLog
    {
        try {
            return EmailLog::create([
                'created_by' => $payload['created_by'] ?? null,
                'mode' => (string) ($payload['mode'] ?? 'auto_invoice'),
                'template_key' => (string) ($payload['template_key'] ?? ''),
                'recipient_email' => (string) ($payload['recipient_email'] ?? ''),
                'reply_to' => $payload['reply_to'] ?? null,
                'cc' => $payload['cc'] ?? null,
                'bcc' => $payload['bcc'] ?? null,
                'subject' => (string) ($payload['subject'] ?? ''),
                'body_preview' => $payload['body_preview'] ?? null,
                'status' => (string) ($payload['status'] ?? 'sent'),
                'error_message' => $payload['error_message'] ?? null,
                'sent_at' => $payload['sent_at'] ?? null,
                'provider_message_id' => $payload['provider_message_id'] ?? null,
                'provider_status' => $payload['provider_status'] ?? null,
                'provider_last_event_at' => $payload['provider_last_event_at'] ?? null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }

    private function normalizeRecipient(?string $email): ?string
    {
        $email = trim((string) $email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private function clientGreeting(Client $client): string
    {
        $name = trim((string) ($client->name ?? ''));
        return $name !== '' ? $name : 'there';
    }

    private function formatAmountLine(ClientInvoice $invoice): ?string
    {
        $amount = number_format((float) ($invoice->amount ?? 0), 2, '.', '');
        $currency = strtoupper((string) ($invoice->currency ?: 'USD'));

        return 'Amount: ' . $currency . ' ' . $amount;
    }

    private function formatDueDateValue(ClientInvoice $invoice): ?string
    {
        if (!$invoice->due_date) {
            return null;
        }

        return $invoice->due_date instanceof \Illuminate\Support\Carbon
            ? $invoice->due_date->format('Y-m-d')
            : (string) $invoice->due_date;
    }

    private function formatDueDateLine(ClientInvoice $invoice): ?string
    {
        $dueDate = $this->formatDueDateValue($invoice);
        return $dueDate ? 'Due date: ' . $dueDate : null;
    }
}
