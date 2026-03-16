<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_project_assignments')) {
            return;
        }

        Schema::create('client_project_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_project_id')->constrained('client_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['client_project_id', 'user_id'], 'client_project_assignments_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_project_assignments');
    }
};
