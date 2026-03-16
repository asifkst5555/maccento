<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('outbound_webhook_deliveries')) {
            return;
        }

        Schema::create('outbound_webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 60);
            $table->string('url', 255);
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_webhook_deliveries');
    }
};
