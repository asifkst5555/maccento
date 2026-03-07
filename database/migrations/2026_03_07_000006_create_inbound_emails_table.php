<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_emails', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40)->default('sendgrid')->index();
            $table->string('from_email', 255)->index();
            $table->string('from_name', 160)->nullable();
            $table->string('to_email', 255)->nullable()->index();
            $table->string('subject', 255)->nullable()->index();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('status', 40)->default('received')->index();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('client_project_id')->nullable()->constrained('client_projects')->nullOnDelete();
            $table->longText('raw_headers')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('received_at')->nullable()->index();
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_emails');
    }
};
