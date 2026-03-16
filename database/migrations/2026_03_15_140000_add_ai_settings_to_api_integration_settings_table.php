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
            if (!Schema::hasColumn('api_integration_settings', 'ai_provider')) {
                $table->string('ai_provider')->nullable();
            }
            if (!Schema::hasColumn('api_integration_settings', 'ai_model')) {
                $table->string('ai_model')->nullable();
            }
            if (!Schema::hasColumn('api_integration_settings', 'openai_api_key')) {
                $table->string('openai_api_key')->nullable();
            }
            if (!Schema::hasColumn('api_integration_settings', 'openai_base_url')) {
                $table->string('openai_base_url')->nullable();
            }
            if (!Schema::hasColumn('api_integration_settings', 'openrouter_api_key')) {
                $table->string('openrouter_api_key')->nullable();
            }
            if (!Schema::hasColumn('api_integration_settings', 'openrouter_base_url')) {
                $table->string('openrouter_base_url')->nullable();
            }
            if (!Schema::hasColumn('api_integration_settings', 'openrouter_model')) {
                $table->string('openrouter_model')->nullable();
            }
            if (!Schema::hasColumn('api_integration_settings', 'gemini_api_key')) {
                $table->string('gemini_api_key')->nullable();
            }
            if (!Schema::hasColumn('api_integration_settings', 'gemini_base_url')) {
                $table->string('gemini_base_url')->nullable();
            }
            if (!Schema::hasColumn('api_integration_settings', 'gemini_model')) {
                $table->string('gemini_model')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('api_integration_settings')) {
            return;
        }

        Schema::table('api_integration_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'ai_provider',
                'ai_model',
                'openai_api_key',
                'openai_base_url',
                'openrouter_api_key',
                'openrouter_base_url',
                'openrouter_model',
                'gemini_api_key',
                'gemini_base_url',
                'gemini_model',
            ]);
        });
    }
};
