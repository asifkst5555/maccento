<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watermark_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('opacity_percent')->default(62)->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('watermark_settings', function (Blueprint $table): void {
            $table->dropColumn('opacity_percent');
        });
    }
};
