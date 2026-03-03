<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_builds', function (Blueprint $table): void {
            $table->id();
            $table->string('quote_id', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('conversation_id')->nullable();
            $table->foreignId('lead_profile_id')->nullable()->constrained('lead_profiles')->nullOnDelete();
            $table->string('visitor_id', 100)->nullable()->index();
            $table->string('status', 30)->default('new')->index();
            $table->string('listing_type', 30)->nullable();
            $table->json('services')->nullable();
            $table->json('options')->nullable();
            $table->json('line_items')->nullable();
            $table->integer('estimated_total')->default(0);
            $table->string('currency', 8)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('conversations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_builds');
    }
};
