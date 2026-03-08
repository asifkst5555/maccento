<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_project_media')) {
            return;
        }

        $hasWatermarkDisk = Schema::hasColumn('client_project_media', 'watermark_disk');
        $hasWatermarkPath = Schema::hasColumn('client_project_media', 'watermark_path');
        if ($hasWatermarkDisk && $hasWatermarkPath) {
            return;
        }

        Schema::table('client_project_media', function (Blueprint $table): void {
            if (!Schema::hasColumn('client_project_media', 'watermark_disk')) {
                $table->string('watermark_disk', 50)->nullable()->after('path');
            }

            if (!Schema::hasColumn('client_project_media', 'watermark_path')) {
                $table->string('watermark_path')->nullable()->after('watermark_disk');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_project_media')) {
            return;
        }

        Schema::table('client_project_media', function (Blueprint $table): void {
            $columnsToDrop = [];

            if (Schema::hasColumn('client_project_media', 'watermark_disk')) {
                $columnsToDrop[] = 'watermark_disk';
            }

            if (Schema::hasColumn('client_project_media', 'watermark_path')) {
                $columnsToDrop[] = 'watermark_path';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};