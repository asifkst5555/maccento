<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('email_logs')) {
            return;
        }

        $hasMessageId = Schema::hasColumn('email_logs', 'provider_message_id');
        $hasStatus = Schema::hasColumn('email_logs', 'provider_status');
        $hasLastEvent = Schema::hasColumn('email_logs', 'provider_last_event_at');
        if ($hasMessageId && $hasStatus && $hasLastEvent) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table): void {
            if (!Schema::hasColumn('email_logs', 'provider_message_id')) {
                $table->string('provider_message_id', 255)->nullable()->after('sent_at')->index();
            }

            if (!Schema::hasColumn('email_logs', 'provider_status')) {
                $table->string('provider_status', 64)->nullable()->after('provider_message_id')->index();
            }

            if (!Schema::hasColumn('email_logs', 'provider_last_event_at')) {
                $table->timestamp('provider_last_event_at')->nullable()->after('provider_status')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table): void {
            $columnsToDrop = [];

            if (Schema::hasColumn('email_logs', 'provider_last_event_at')) {
                $columnsToDrop[] = 'provider_last_event_at';
            }

            if (Schema::hasColumn('email_logs', 'provider_status')) {
                $columnsToDrop[] = 'provider_status';
            }

            if (Schema::hasColumn('email_logs', 'provider_message_id')) {
                $columnsToDrop[] = 'provider_message_id';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
