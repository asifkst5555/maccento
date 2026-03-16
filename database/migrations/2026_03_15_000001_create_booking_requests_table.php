<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_project_id')->nullable()->constrained('client_projects')->nullOnDelete();
            $table->foreignId('lead_profile_id')->nullable()->constrained('lead_profiles')->nullOnDelete();
            $table->foreignId('requester_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requested_service', 160);
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time_window', 80)->nullable();
            $table->json('alternate_slots')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();

            $table->index(['status', 'preferred_date']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};
