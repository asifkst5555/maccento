<?php

namespace App\Console\Commands;

use App\Models\DropboxImportFileLog;
use App\Models\DropboxImportSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupCloudImportsCommand extends Command
{
    protected $signature = 'cloud-import:cleanup';
    protected $description = 'Clean temporary download files, expired sessions, and old file log records.';

    public function handle(): void
    {
        $this->info('Starting Cloud Import cleanup task...');

        // 1. Clean temporary downloads older than 12 hours
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/cloud_import_*');
        $deletedFiles = 0;
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file) > 43200)) {
                @unlink($file);
                $deletedFiles++;
            }
        }
        $this->info("Pruned {$deletedFiles} temporary download files.");

        // 2. Mark sessions stuck in "importing" or "pending" for > 24 hours as failed/expired
        $stuckSessions = DropboxImportSession::whereIn('status', ['importing', 'pending'])
            ->where('updated_at', '<', now()->subHours(24))
            ->get();

        foreach ($stuckSessions as $session) {
            $session->update([
                'status' => 'failed',
                'error_log' => array_merge($session->error_log ?? [], [
                    ['filename' => 'System Cleanup', 'error' => 'Import session marked abandoned/stuck after 24 hours of inactivity.']
                ])
            ]);
            $this->warn("Marked stuck session #{$session->id} ({$session->uuid}) as failed.");
        }

        // 3. Prune file logs older than 30 days
        $prunedLogs = DropboxImportFileLog::where('created_at', '<', now()->subDays(30))->delete();
        $this->info("Pruned {$prunedLogs} old file log rows.");

        $this->info('Cloud Import cleanup task completed successfully.');
    }
}
