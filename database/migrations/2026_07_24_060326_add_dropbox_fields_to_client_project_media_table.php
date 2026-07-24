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
        Schema::table('client_project_media', function (Blueprint $table) {
            $table->string('dropbox_file_id', 191)->nullable()->after('size_bytes')->index();
            $table->string('file_hash', 64)->nullable()->after('dropbox_file_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_project_media', function (Blueprint $table) {
            $table->dropIndex(['client_project_media_dropbox_file_id_index']);
            $table->dropIndex(['client_project_media_file_hash_index']);
            $table->dropColumn(['dropbox_file_id', 'file_hash']);
        });
    }
};
