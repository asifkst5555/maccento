<?php

namespace App\Console\Commands;

use App\Models\ClientInvoice;
use App\Models\PanelNotification;
use App\Services\PanelNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendUnpaidInvoiceReminders extends Command
{
    protected $signature = 'invoices:unpaid-reminders {--dry-run : Preview the reminder without sending notifications} {--force : Send even if a reminder was already sent today}';

    protected $description = 'Send internal reminders for unpaid invoices that are due or overdue.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForced = (bool) $this->option('force');
        $today = now()->toDateString();

        if (!$isForced) {
            $alreadySent = PanelNotification::query()
                ->where('type', 'invoice_unpaid_reminder')
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadySent) {
                $this->info("Unpaid invoice reminder already sent for {$today}.");
                return self::SUCCESS;
            }
        }

        $baseQuery = ClientInvoice::query()
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today);

        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            $this->info("No unpaid invoices due on or before {$today}.");
            return self::SUCCESS;
        }

        $sample = (clone $baseQuery)
            ->with(['client:id,name'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'client_id', 'invoice_number', 'due_date', 'amount', 'currency', 'status']);

        $lines = [];
        foreach ($sample as $invoice) {
            $clientName = trim((string) ($invoice->client?->name ?? 'Unknown client'));
            if ($clientName === '') {
                $clientName = 'Unknown client';
            }
            $currency = strtoupper((string) ($invoice->currency ?: 'USD'));
            $amount = number_format((float) ($invoice->amount ?? 0), 2, '.', '');
            $dueDate = $invoice->due_date instanceof Carbon
                ? $invoice->due_date->format('Y-m-d')
                : (string) ($invoice->due_date ?? '');
            $status = ucfirst((string) ($invoice->status ?? ''));

            $lines[] = trim(implode(' - ', array_filter([
                $invoice->invoice_number,
                $clientName,
                $dueDate !== '' ? 'Due ' . $dueDate : null,
                $currency . ' ' . $amount,
                $status !== '' ? $status : null,
            ])));
        }

        if ($total > $sample->count()) {
            $lines[] = 'And ' . ($total - $sample->count()) . ' more.';
        }

        $body = implode("\n", array_merge([
            "Unpaid invoices due on or before {$today}: {$total}.",
        ], $lines));

        if ($isDryRun) {
            $this->line('Dry run: reminder not sent.');
            $this->newLine();
            $this->line($body);
            return self::SUCCESS;
        }

        app(PanelNotificationService::class)->notifyInternal(
            'invoice_unpaid_reminder',
            'Unpaid invoices need attention',
            $body,
            route('admin.invoices.index'),
            [
                'as_of' => $today,
                'total' => $total,
                'invoice_ids' => $sample->pluck('id')->all(),
            ]
        );

        $this->info("Sent unpaid invoice reminder for {$today} (total {$total}).");

        return self::SUCCESS;
    }
}
