<?php

namespace App\Console\Commands;

use App\Models\ClientInvoice;
use App\Models\InvoiceSetting;
use App\Services\InvoiceEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SendInvoiceDueReminders extends Command
{
    protected $signature = 'invoices:send-due-reminders {--dry-run : Preview due reminders without sending emails}';

    protected $description = 'Send invoice reminder emails 3 days before due date and on the due date.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $now = now();
        $today = $now->toDateString();

        $settings = $this->resolveInvoiceSettings();
        if (!(bool) ($settings->reminder_enabled ?? true)) {
            $this->info('Invoice reminders are disabled in settings.');
            return self::SUCCESS;
        }

        $daysBefore = max(1, (int) ($settings->reminder_days_before ?? 3));
        $threeDaysOut = $now->copy()->addDays($daysBefore)->toDateString();
        $sendOnDueDate = (bool) ($settings->reminder_send_on_due_date ?? true);
        $overdueEnabled = (bool) ($settings->overdue_reminder_enabled ?? true);
        $overdueEveryDays = max(1, (int) ($settings->overdue_reminder_every_days ?? 3));
        $emailService = app(InvoiceEmailService::class);

        $stats = [
            'three_day_candidates' => 0,
            'three_day_sent' => 0,
            'due_today_candidates' => 0,
            'due_today_sent' => 0,
            'overdue_candidates' => 0,
            'overdue_sent' => 0,
        ];

        $threeDayInvoices = ClientInvoice::query()
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereDate('due_date', $threeDaysOut)
            ->with('client:id,name,email')
            ->get();

        $stats['three_day_candidates'] = $threeDayInvoices->count();

        foreach ($threeDayInvoices as $invoice) {
            if (!$invoice->client) {
                continue;
            }

            if ($isDryRun) {
                $stats['three_day_sent']++;
                continue;
            }

            if ($emailService->sendDueReminder($invoice, $invoice->client, $daysBefore)) {
                $stats['three_day_sent']++;
            }
        }

        if ($sendOnDueDate) {
            $dueTodayInvoices = ClientInvoice::query()
                ->whereIn('status', ['sent', 'partial', 'overdue'])
                ->whereDate('due_date', $today)
                ->with('client:id,name,email')
                ->get();

            $stats['due_today_candidates'] = $dueTodayInvoices->count();

            foreach ($dueTodayInvoices as $invoice) {
                if (!$invoice->client) {
                    continue;
                }

                if ($isDryRun) {
                    $stats['due_today_sent']++;
                    continue;
                }

                if ($emailService->sendDueReminder($invoice, $invoice->client, 0)) {
                    $stats['due_today_sent']++;
                }
            }
        }

        if ($overdueEnabled) {
            $overdueInvoices = ClientInvoice::query()
                ->whereIn('status', ['sent', 'partial', 'overdue'])
                ->whereDate('due_date', '<', $today)
                ->with('client:id,name,email')
                ->get();

            $stats['overdue_candidates'] = $overdueInvoices->count();

            foreach ($overdueInvoices as $invoice) {
                if (!$invoice->client || !$invoice->due_date) {
                    continue;
                }

                $daysPastDue = $invoice->due_date->diffInDays($now);
                if ($daysPastDue < $overdueEveryDays || ($daysPastDue % $overdueEveryDays) !== 0) {
                    continue;
                }

                if ($isDryRun) {
                    $stats['overdue_sent']++;
                    continue;
                }

                if ($emailService->sendOverdueReminder($invoice, $invoice->client, $daysPastDue)) {
                    $stats['overdue_sent']++;
                }
            }
        }

        $this->line('Invoice Due Reminder Summary');
        $this->line($daysBefore . '-day reminders: ' . $stats['three_day_sent'] . ' sent / ' . $stats['three_day_candidates'] . ' eligible.');
        $this->line('Due today reminders: ' . $stats['due_today_sent'] . ' sent / ' . $stats['due_today_candidates'] . ' eligible.');
        $this->line('Overdue reminders (every ' . $overdueEveryDays . ' days): ' . $stats['overdue_sent'] . ' sent / ' . $stats['overdue_candidates'] . ' eligible.');

        return self::SUCCESS;
    }

    private function resolveInvoiceSettings(): InvoiceSetting
    {
        $settings = InvoiceSetting::query()->first();
        if ($settings) {
            return $settings;
        }

        $defaults = [
            'stripe_enabled' => false,
            'paypal_enabled' => false,
            'manual_enabled' => true,
            'manual_instructions' => 'Pay by bank transfer or cash. Please reference your invoice number.',
            'include_tax_on_pdf' => false,
            'tax_rate_percent' => 0,
            'auto_email_on_invoice_create' => true,
            'reminder_enabled' => true,
            'reminder_days_before' => 3,
            'reminder_send_on_due_date' => true,
            'overdue_reminder_enabled' => true,
            'overdue_reminder_every_days' => 3,
        ];

        $allowed = array_filter($defaults, static function ($value, $column): bool {
            return Schema::hasColumn('invoice_settings', (string) $column);
        }, ARRAY_FILTER_USE_BOTH);

        return InvoiceSetting::query()->create($allowed);
    }
}
