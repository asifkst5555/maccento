<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_project_media', function (Blueprint $table): void {
            $table->string('watermark_signature', 120)->nullable()->after('watermark_path');
        });
    }

    public function down(): void
    {
        Schema::table('client_project_media', function (Blueprint $table): void {
            $table->dropColumn('watermark_signature');
        });
    }
};
