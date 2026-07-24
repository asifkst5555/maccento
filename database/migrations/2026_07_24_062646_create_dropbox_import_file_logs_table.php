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
        Schema::create('dropbox_import_file_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dropbox_import_session_id')->constrained('dropbox_import_sessions')->cascadeOnDelete();
            $table->string('filename');
            $table->string('dropbox_file_id')->nullable();
            $table->string('status', 32); // completed, skipped, failed
            $table->text('error_message')->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->string('file_hash')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dropbox_import_file_logs');
    }
};
