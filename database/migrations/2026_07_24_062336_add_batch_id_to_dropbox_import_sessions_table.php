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
        Schema::table('dropbox_import_sessions', function (Blueprint $table) {
            $table->string('batch_id', 36)->nullable()->after('uuid');
        });

        Schema::table('client_project_media', function (Blueprint $table) {
            $table->string('folder_path')->nullable()->after('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dropbox_import_sessions', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });

        Schema::table('client_project_media', function (Blueprint $table) {
            $table->dropColumn('folder_path');
        });
    }
};
