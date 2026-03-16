<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('api_integration_settings')) {
            return;
        }

        Schema::create('api_integration_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('stripe_publishable_key')->nullable();
            $table->string('stripe_secret_key')->nullable();
            $table->string('paypal_client_id')->nullable();
            $table->string('paypal_secret')->nullable();
            $table->boolean('paypal_sandbox')->default(true);
            $table->string('mail_mailer')->nullable();
            $table->string('mail_host')->nullable();
            $table->unsignedInteger('mail_port')->nullable();
            $table->string('mail_username')->nullable();
            $table->string('mail_password')->nullable();
            $table->string('mail_encryption')->nullable();
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->string('chat_provider')->nullable();
            $table->string('chat_api_key')->nullable();
            $table->string('chat_webhook_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_integration_settings');
    }
};