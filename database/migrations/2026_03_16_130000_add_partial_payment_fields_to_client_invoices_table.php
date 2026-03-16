<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('client_invoices')) {
            return;
        }

        Schema::table('client_invoices', function (Blueprint $table): void {
            if (!Schema::hasColumn('client_invoices', 'amount_paid')) {
                $table->decimal('amount_paid', 12, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('client_invoices', 'balance_due')) {
                $table->decimal('balance_due', 12, 2)->default(0)->after('amount_paid');
            }
        });

        if (Schema::hasColumn('client_invoices', 'amount') && Schema::hasColumn('client_invoices', 'balance_due')) {
            DB::table('client_invoices')
                ->where('balance_due', 0)
                ->update(['balance_due' => DB::raw('amount')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_invoices')) {
            return;
        }

        Schema::table('client_invoices', function (Blueprint $table): void {
            if (Schema::hasColumn('client_invoices', 'balance_due')) {
                $table->dropColumn('balance_due');
            }
            if (Schema::hasColumn('client_invoices', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
        });
    }
};
