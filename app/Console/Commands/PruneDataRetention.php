<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneDataRetention extends Command
{
    protected $signature = 'system:prune-data';
    protected $description = 'Delete records beyond data retention windows.';

    public function handle(): int
    {
        $retention = (array) config('compliance.retention_days', []);

        $this->pruneTable('request_edit_logs', 'created_at', (int) ($retention['request_edit_logs'] ?? 365));
        $this->pruneTable('email_logs', 'created_at', (int) ($retention['email_logs'] ?? 365));
        $this->pruneTable('outbound_webhook_deliveries', 'created_at', (int) ($retention['outbound_webhook_deliveries'] ?? 90));
        $this->pruneTable('panel_notifications', 'created_at', (int) ($retention['panel_notifications'] ?? 90));

        $this->line('Retention pruning complete.');
        return self::SUCCESS;
    }

    private function pruneTable(string $table, string $column, int $days): void
    {
        if ($days <= 0 || !Schema::hasTable($table)) {
            return;
        }

        $cutoff = now()->subDays($days);
        $deleted = DB::table($table)->where($column, '<', $cutoff)->delete();
        $this->line("{$table}: deleted {$deleted} record(s) older than {$days} days.");
    }
}
