<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('request_edit_logs')) {
            return;
        }

        Schema::table('request_edit_logs', function (Blueprint $table): void {
            if (!Schema::hasColumn('request_edit_logs', 'entity_type')) {
                $table->string('entity_type', 40)->nullable()->after('request_type');
            }
            if (!Schema::hasColumn('request_edit_logs', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            }
            if (!Schema::hasColumn('request_edit_logs', 'action')) {
                $table->string('action', 40)->nullable()->after('actor_role');
            }
            if (!Schema::hasColumn('request_edit_logs', 'summary')) {
                $table->string('summary', 255)->nullable()->after('action');
            }
            if (!Schema::hasColumn('request_edit_logs', 'ip_address')) {
                $table->string('ip_address', 64)->nullable()->after('summary');
            }
            if (!Schema::hasColumn('request_edit_logs', 'user_agent')) {
                $table->string('user_agent', 255)->nullable()->after('ip_address');
            }
        });

        Schema::table('request_edit_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('request_edit_logs', 'entity_type') && Schema::hasColumn('request_edit_logs', 'entity_id')) {
                $table->index(['entity_type', 'entity_id']);
            }
            if (Schema::hasColumn('request_edit_logs', 'action')) {
                $table->index(['action', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('request_edit_logs')) {
            return;
        }

        Schema::table('request_edit_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('request_edit_logs', 'entity_type') && Schema::hasColumn('request_edit_logs', 'entity_id')) {
                $table->dropIndex(['entity_type', 'entity_id']);
            }
            if (Schema::hasColumn('request_edit_logs', 'action')) {
                $table->dropIndex(['action', 'created_at']);
            }
            if (Schema::hasColumn('request_edit_logs', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
            if (Schema::hasColumn('request_edit_logs', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
            if (Schema::hasColumn('request_edit_logs', 'summary')) {
                $table->dropColumn('summary');
            }
            if (Schema::hasColumn('request_edit_logs', 'action')) {
                $table->dropColumn('action');
            }
            if (Schema::hasColumn('request_edit_logs', 'entity_id')) {
                $table->dropColumn('entity_id');
            }
            if (Schema::hasColumn('request_edit_logs', 'entity_type')) {
                $table->dropColumn('entity_type');
            }
        });
    }
};
