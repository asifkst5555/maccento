<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all unread system_health_alert notifications grouped by user_id
        $usersWithAlerts = DB::table('panel_notifications')
            ->where('type', 'system_health_alert')
            ->whereNull('read_at')
            ->select('user_id')
            ->distinct()
            ->pluck('user_id')
            ->all();

        foreach ($usersWithAlerts as $userId) {
            // Find the latest unread health alert for this user
            $latestAlertId = DB::table('panel_notifications')
                ->where('type', 'system_health_alert')
                ->whereNull('read_at')
                ->where('user_id', $userId)
                ->latest('id')
                ->value('id');

            if ($latestAlertId) {
                // Delete all other unread health alerts for this user
                DB::table('panel_notifications')
                    ->where('type', 'system_health_alert')
                    ->whereNull('read_at')
                    ->where('user_id', $userId)
                    ->where('id', '<>', $latestAlertId)
                    ->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
