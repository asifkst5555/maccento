<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_project_media', function (Blueprint $table): void {
            if (!Schema::hasColumn('client_project_media', 'delivery_stage')) {
                $table->string('delivery_stage', 32)->nullable()->after('type')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_project_media', function (Blueprint $table): void {
            if (Schema::hasColumn('client_project_media', 'delivery_stage')) {
                $table->dropIndex(['delivery_stage']);
                $table->dropColumn('delivery_stage');
            }
        });
    }
};
