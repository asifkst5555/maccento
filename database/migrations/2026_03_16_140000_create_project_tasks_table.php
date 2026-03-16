<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('project_tasks')) {
            return;
        }

        Schema::create('project_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_project_id')->constrained('client_projects')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('open');
            $table->date('due_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['client_project_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
