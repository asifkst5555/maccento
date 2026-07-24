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
        Schema::create('cloud_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->unique();
            $table->boolean('is_active')->default(false);
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->longText('additional_settings')->nullable(); // JSON configuration
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloud_provider_settings');
    }
};
