<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sendgrid_webhook_events', function (Blueprint $table): void {
            $table->foreignId('email_log_id')->nullable()->after('id')->constrained('email_logs')->nullOnDelete()->index();
        });
    }

    public function down(): void
    {
        Schema::table('sendgrid_webhook_events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('email_log_id');
        });
    }
};
