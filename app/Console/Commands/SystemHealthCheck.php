<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use App\Services\PanelNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SystemHealthCheck extends Command
{
    protected $signature = 'system:health-check {--notify : Send internal notifications when issues are found}';
    protected $description = 'Check queue, email, and backup health.';

    public function handle(PanelNotificationService $notificationService): int
    {
        $issues = [];
        $now = now();
        $windowStart = $now->copy()->subHours(24);

        $failedJobsCount = null;
        $latestFailedJobAt = null;
        if (Schema::hasTable('failed_jobs')) {
            $failedJobsCount = (int) DB::table('failed_jobs')
                ->where('failed_at', '>=', $windowStart)
                ->count();
            $latestFailedJobAt = DB::table('failed_jobs')->max('failed_at');
        }

        $failedEmailCount = null;
        if (Schema::hasTable('email_logs')) {
            $failedEmailCount = (int) EmailLog::query()
                ->where('status', 'failed')
                ->where('created_at', '>=', $windowStart)
                ->count();
        }

        $backupFiles = Storage::disk('local')->exists('backups')
            ? Storage::disk('local')->files('backups')
            : [];

        $backupLatest = null;
        foreach ($backupFiles as $file) {
            $timestamp = Storage::disk('local')->lastModified($file);
            if ($backupLatest === null || $timestamp > $backupLatest) {
                $backupLatest = $timestamp;
            }
        }

        $backupLatestAt = $backupLatest ? Carbon::createFromTimestamp($backupLatest) : null;
        $backupAgeDays = $backupLatestAt ? $backupLatestAt->diffInDays($now) : null;

        $failedJobsThreshold = (int) config('system_health.failed_jobs_threshold', 1);
        $failedEmailThreshold = (int) config('system_health.failed_email_threshold', 1);
        $backupMaxAgeDays = (int) config('system_health.backup_max_age_days', 2);

        if ($failedJobsCount !== null && $failedJobsCount >= $failedJobsThreshold) {
            $issues[] = "Failed jobs in last 24h: {$failedJobsCount}";
        }
        if ($failedEmailCount !== null && $failedEmailCount >= $failedEmailThreshold) {
            $issues[] = "Failed emails in last 24h: {$failedEmailCount}";
        }
        if ($backupLatestAt === null) {
            $issues[] = 'No database backup files found.';
        } elseif ($backupAgeDays !== null && $backupAgeDays >= $backupMaxAgeDays) {
            $issues[] = "Last database backup is {$backupAgeDays} day(s) old.";
        }

        $this->line('System Health Summary');
        $this->line('Failed jobs (24h): ' . ($failedJobsCount ?? 'n/a'));
        $this->line('Failed emails (24h): ' . ($failedEmailCount ?? 'n/a'));
        $this->line('Latest backup: ' . ($backupLatestAt?->format('Y-m-d H:i') ?? 'none'));
        $this->line('Latest failed job: ' . ($latestFailedJobAt ? Carbon::parse((string) $latestFailedJobAt)->format('Y-m-d H:i') : 'none'));

        if ($this->option('notify') && $issues !== []) {
            $cacheKey = 'system_health.notify:' . md5(implode('|', $issues));
            if (!Cache::has($cacheKey)) {
                $notificationService->notifyInternal(
                    'system_health_alert',
                    'System health needs attention',
                    implode(' | ', $issues),
                    route('admin.system-health.index')
                );
                Cache::put($cacheKey, true, now()->addMinutes(90));
            }
        }

        return self::SUCCESS;
    }
}
