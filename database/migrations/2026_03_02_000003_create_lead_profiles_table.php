<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('conversation_id')->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable()->index();
            $table->string('service_type')->nullable()->index();
            $table->string('property_type')->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('budget_min')->nullable();
            $table->unsignedInteger('budget_max')->nullable();
            $table->string('timeline')->nullable();
            $table->string('decision_maker', 40)->nullable();
            $table->string('preferred_contact', 20)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('score')->default(0)->index();
            $table->timestamp('qualified_at')->nullable()->index();
            $table->string('status', 30)->default('new')->index();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_profiles');
    }
};
