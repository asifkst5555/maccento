<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('default_currency', 8)->default('USD');
            $table->json('enabled_currencies')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_settings');
    }
};
