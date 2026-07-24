<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dropbox_import_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('client_project_id')->constrained('client_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('folder_url');
            $table->string('provider', 50)->default('dropbox');
            $table->string('status', 32)->default('pending'); // pending, scanning, importing, completed, cancelled, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->integer('total_files')->default(0);
            $table->integer('processed_files')->default(0);
            $table->integer('imported_files')->default(0);
            $table->integer('duplicate_files')->default(0);
            $table->integer('failed_files')->default(0);
            $table->bigInteger('total_size')->default(0); // in bytes
            $table->string('current_file')->nullable();
            $table->string('media_stage', 32)->default('raw');
            $table->longText('duplicate_report')->nullable(); // JSON duplicate details
            $table->longText('error_log')->nullable(); // failed file details or scan error log
            $table->longText('files_queue')->nullable(); // JSON list of files to process
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dropbox_import_sessions');
    }
};
