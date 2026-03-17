<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('api_integration_settings')) {
            return;
        }

        Schema::table('api_integration_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_enabled')) {
                $table->boolean('inbound_mail_enabled')->nullable()->after('mail_from_name');
            }
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_provider')) {
                $table->string('inbound_mail_provider', 20)->nullable()->after('inbound_mail_enabled');
            }
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_host')) {
                $table->string('inbound_mail_host', 160)->nullable()->after('inbound_mail_provider');
            }
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_port')) {
                $table->unsignedInteger('inbound_mail_port')->nullable()->after('inbound_mail_host');
            }
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_encryption')) {
                $table->string('inbound_mail_encryption', 20)->nullable()->after('inbound_mail_port');
            }
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_username')) {
                $table->string('inbound_mail_username', 160)->nullable()->after('inbound_mail_encryption');
            }
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_password')) {
                $table->string('inbound_mail_password', 255)->nullable()->after('inbound_mail_username');
            }
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_mailbox')) {
                $table->string('inbound_mail_mailbox', 120)->nullable()->after('inbound_mail_password');
            }
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_search')) {
                $table->string('inbound_mail_search', 40)->nullable()->after('inbound_mail_mailbox');
            }
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_max_per_run')) {
                $table->unsignedInteger('inbound_mail_max_per_run')->nullable()->after('inbound_mail_search');
            }
            if (!Schema::hasColumn('api_integration_settings', 'inbound_mail_delete_after_process')) {
                $table->boolean('inbound_mail_delete_after_process')->nullable()->after('inbound_mail_max_per_run');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('api_integration_settings')) {
            return;
        }

        Schema::table('api_integration_settings', function (Blueprint $table): void {
            $columns = [
                'inbound_mail_enabled',
                'inbound_mail_provider',
                'inbound_mail_host',
                'inbound_mail_port',
                'inbound_mail_encryption',
                'inbound_mail_username',
                'inbound_mail_password',
                'inbound_mail_mailbox',
                'inbound_mail_search',
                'inbound_mail_max_per_run',
                'inbound_mail_delete_after_process',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('api_integration_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
