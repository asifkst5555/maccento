<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('invoice_settings', 'auto_email_on_invoice_create')) {
                $table->boolean('auto_email_on_invoice_create')->default(true);
            }
            if (!Schema::hasColumn('invoice_settings', 'reminder_enabled')) {
                $table->boolean('reminder_enabled')->default(true);
            }
            if (!Schema::hasColumn('invoice_settings', 'reminder_days_before')) {
                $table->unsignedInteger('reminder_days_before')->default(3);
            }
            if (!Schema::hasColumn('invoice_settings', 'reminder_send_on_due_date')) {
                $table->boolean('reminder_send_on_due_date')->default(true);
            }
            if (!Schema::hasColumn('invoice_settings', 'overdue_reminder_enabled')) {
                $table->boolean('overdue_reminder_enabled')->default(true);
            }
            if (!Schema::hasColumn('invoice_settings', 'overdue_reminder_every_days')) {
                $table->unsignedInteger('overdue_reminder_every_days')->default(3);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('invoice_settings', 'auto_email_on_invoice_create')) {
                $table->dropColumn('auto_email_on_invoice_create');
            }
            if (Schema::hasColumn('invoice_settings', 'reminder_enabled')) {
                $table->dropColumn('reminder_enabled');
            }
            if (Schema::hasColumn('invoice_settings', 'reminder_days_before')) {
                $table->dropColumn('reminder_days_before');
            }
            if (Schema::hasColumn('invoice_settings', 'reminder_send_on_due_date')) {
                $table->dropColumn('reminder_send_on_due_date');
            }
            if (Schema::hasColumn('invoice_settings', 'overdue_reminder_enabled')) {
                $table->dropColumn('overdue_reminder_enabled');
            }
            if (Schema::hasColumn('invoice_settings', 'overdue_reminder_every_days')) {
                $table->dropColumn('overdue_reminder_every_days');
            }
        });
    }
};
