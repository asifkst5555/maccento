<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table): void {
            $table->string('provider_message_id', 255)->nullable()->after('sent_at')->index();
            $table->string('provider_status', 64)->nullable()->after('provider_message_id')->index();
            $table->timestamp('provider_last_event_at')->nullable()->after('provider_status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table): void {
            $table->dropColumn(['provider_last_event_at', 'provider_status', 'provider_message_id']);
        });
    }
};
