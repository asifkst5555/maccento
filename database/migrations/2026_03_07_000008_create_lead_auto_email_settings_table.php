<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_auto_email_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 80)->unique();
            $table->boolean('enabled')->default(true);
            $table->string('tone', 40)->default('professional');
            $table->text('template_prompt')->nullable();
            $table->string('subject_prefix', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_auto_email_settings');
    }
};
