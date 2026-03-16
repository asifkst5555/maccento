<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_project_comments')) {
            return;
        }

        Schema::create('client_project_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_project_id')->constrained('client_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_role', 30)->default('client');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_project_comments');
    }
};
