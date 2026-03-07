<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sendgrid_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 64)->index();
            $table->string('email', 255)->nullable()->index();
            $table->string('sg_message_id', 255)->nullable()->index();
            $table->string('sg_event_id', 255)->nullable()->index();
            $table->json('category')->nullable();
            $table->json('payload');
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sendgrid_webhook_events');
    }
};
