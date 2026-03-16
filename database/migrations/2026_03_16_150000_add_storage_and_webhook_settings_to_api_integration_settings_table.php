<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('api_integration_settings')) {
            return;
        }

        Schema::table('api_integration_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('api_integration_settings', 'media_disk')) {
                $table->string('media_disk', 20)->nullable()->after('mail_from_name');
            }
            if (!Schema::hasColumn('api_integration_settings', 's3_key')) {
                $table->string('s3_key', 255)->nullable()->after('media_disk');
            }
            if (!Schema::hasColumn('api_integration_settings', 's3_secret')) {
                $table->string('s3_secret', 255)->nullable()->after('s3_key');
            }
            if (!Schema::hasColumn('api_integration_settings', 's3_region')) {
                $table->string('s3_region', 120)->nullable()->after('s3_secret');
            }
            if (!Schema::hasColumn('api_integration_settings', 's3_bucket')) {
                $table->string('s3_bucket', 120)->nullable()->after('s3_region');
            }
            if (!Schema::hasColumn('api_integration_settings', 's3_endpoint')) {
                $table->string('s3_endpoint', 255)->nullable()->after('s3_bucket');
            }
            if (!Schema::hasColumn('api_integration_settings', 's3_path_style')) {
                $table->boolean('s3_path_style')->default(false)->after('s3_endpoint');
            }
            if (!Schema::hasColumn('api_integration_settings', 'outbound_webhook_enabled')) {
                $table->boolean('outbound_webhook_enabled')->default(false)->after('s3_path_style');
            }
            if (!Schema::hasColumn('api_integration_settings', 'outbound_webhook_url')) {
                $table->string('outbound_webhook_url', 255)->nullable()->after('outbound_webhook_enabled');
            }
            if (!Schema::hasColumn('api_integration_settings', 'outbound_webhook_secret')) {
                $table->string('outbound_webhook_secret', 255)->nullable()->after('outbound_webhook_url');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('api_integration_settings')) {
            return;
        }

        Schema::table('api_integration_settings', function (Blueprint $table): void {
            foreach ([
                'outbound_webhook_secret',
                'outbound_webhook_url',
                'outbound_webhook_enabled',
                's3_path_style',
                's3_endpoint',
                's3_bucket',
                's3_region',
                's3_secret',
                's3_key',
                'media_disk',
            ] as $column) {
                if (Schema::hasColumn('api_integration_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
