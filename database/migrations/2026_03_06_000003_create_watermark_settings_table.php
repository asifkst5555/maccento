<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watermark_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('logo_disk', 50)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('position', 30)->default('center');
            $table->string('size', 20)->default('medium');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watermark_settings');
    }
};
