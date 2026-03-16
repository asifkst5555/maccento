<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_invoices', function (Blueprint $table): void {
            if (!Schema::hasColumn('client_invoices', 'payment_provider')) {
                $table->string('payment_provider', 40)->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('client_invoices', 'payment_reference')) {
                $table->string('payment_reference', 120)->nullable()->after('payment_provider');
            }
            if (!Schema::hasColumn('client_invoices', 'payment_method')) {
                $table->string('payment_method', 60)->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('client_invoices', 'payment_meta')) {
                $table->json('payment_meta')->nullable()->after('payment_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_invoices', function (Blueprint $table): void {
            foreach (['payment_meta', 'payment_method', 'payment_reference', 'payment_provider'] as $column) {
                if (Schema::hasColumn('client_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};