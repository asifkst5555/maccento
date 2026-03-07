<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_email', 255)->nullable()->index();
            $table->string('reply_to', 255)->nullable();
            $table->string('cc', 500)->nullable();
            $table->string('bcc', 500)->nullable();
            $table->foreignId('client_project_id')->nullable()->constrained('client_projects')->nullOnDelete();
            $table->string('subject', 255)->nullable();
            $table->longText('message')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_drafts');
    }
};
