<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('clients')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table): void {
            if (!Schema::hasColumn('clients', 'notify_portal')) {
                $table->boolean('notify_portal')->default(true)->after('notes');
            }
            if (!Schema::hasColumn('clients', 'notify_invoice_email')) {
                $table->boolean('notify_invoice_email')->default(true)->after('notify_portal');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('clients')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table): void {
            if (Schema::hasColumn('clients', 'notify_invoice_email')) {
                $table->dropColumn('notify_invoice_email');
            }
            if (Schema::hasColumn('clients', 'notify_portal')) {
                $table->dropColumn('notify_portal');
            }
        });
    }
};
