<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('company', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('service', 80)->nullable();
            $table->string('region', 80)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('new');
            $table->string('source', 60)->default('website_contact_form');
            $table->string('page_url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'id']);
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_form_submissions');
    }
};
