<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sendgrid_webhook_events') || Schema::hasColumn('sendgrid_webhook_events', 'email_log_id')) {
            return;
        }

        Schema::table('sendgrid_webhook_events', function (Blueprint $table): void {
            $table->foreignId('email_log_id')->nullable()->after('id')->constrained('email_logs')->nullOnDelete()->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sendgrid_webhook_events') || !Schema::hasColumn('sendgrid_webhook_events', 'email_log_id')) {
            return;
        }

        Schema::table('sendgrid_webhook_events', function (Blueprint $table): void {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->dropColumn('email_log_id');
                return;
            }

            $table->dropConstrainedForeignId('email_log_id');
        });
    }
};
