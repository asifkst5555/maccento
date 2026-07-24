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
            $table->text('dropbox_shared_link')->nullable()->after('file_hash');
            $table->string('import_source', 50)->nullable()->after('dropbox_shared_link')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_project_media', function (Blueprint $table) {
            $table->dropIndex(['client_project_media_import_source_index']);
            $table->dropColumn(['dropbox_shared_link', 'import_source']);
        });
    }
};
