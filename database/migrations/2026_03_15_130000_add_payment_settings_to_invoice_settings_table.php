<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('invoice_settings', 'stripe_enabled')) {
                $table->boolean('stripe_enabled')->default(false);
            }
            if (!Schema::hasColumn('invoice_settings', 'paypal_enabled')) {
                $table->boolean('paypal_enabled')->default(false);
            }
            if (!Schema::hasColumn('invoice_settings', 'manual_enabled')) {
                $table->boolean('manual_enabled')->default(true);
            }
            if (!Schema::hasColumn('invoice_settings', 'manual_instructions')) {
                $table->text('manual_instructions')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table): void {
            foreach (['stripe_enabled', 'paypal_enabled', 'manual_enabled', 'manual_instructions'] as $column) {
                if (Schema::hasColumn('invoice_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};