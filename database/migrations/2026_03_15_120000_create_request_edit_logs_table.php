<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('request_edit_logs')) {
            return;
        }

        Schema::create('request_edit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('request_type', 32);
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_role', 50)->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index(['request_type', 'request_id']);
            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_edit_logs');
    }
};