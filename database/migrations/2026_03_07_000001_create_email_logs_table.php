<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 20)->index();
            $table->string('template_key', 60)->nullable()->index();
            $table->string('recipient_email', 255)->index();
            $table->string('reply_to', 255)->nullable();
            $table->string('cc', 500)->nullable();
            $table->string('bcc', 500)->nullable();
            $table->string('subject', 180);
            $table->text('body_preview')->nullable();
            $table->string('status', 20)->index();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
