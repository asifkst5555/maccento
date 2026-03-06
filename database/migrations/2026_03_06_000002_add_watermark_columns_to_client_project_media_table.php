<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_project_media', function (Blueprint $table): void {
            $table->string('watermark_disk', 50)->nullable()->after('path');
            $table->string('watermark_path')->nullable()->after('watermark_disk');
        });
    }

    public function down(): void
    {
        Schema::table('client_project_media', function (Blueprint $table): void {
            $table->dropColumn(['watermark_disk', 'watermark_path']);
        });
    }
};