<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('invoices:unpaid-reminders')
            ->dailyAt('09:00');
        $schedule->command('invoices:send-due-reminders')
            ->dailyAt('09:10');
        $schedule->command('system:db-backup --prune --respect-settings')
            ->everyFifteenMinutes();
        $schedule->command('system:health-check --notify')
            ->hourly();
        $schedule->command('projects:task-reminders --notify')
            ->dailyAt('08:15');
        $schedule->command('system:prune-data')
            ->weeklyOn(1, '03:00');
        $schedule->command('inbound:pull')
            ->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
