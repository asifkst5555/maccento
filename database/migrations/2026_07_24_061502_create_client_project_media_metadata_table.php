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
        Schema::create('client_project_media_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_project_media_id')->constrained('client_project_media')->cascadeOnDelete();
            $table->string('camera_make')->nullable();
            $table->string('camera_model')->nullable();
            $table->string('lens')->nullable();
            $table->integer('iso')->nullable();
            $table->string('shutter_speed')->nullable();
            $table->string('aperture')->nullable();
            $table->string('capture_date')->nullable();
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_project_media_metadata');
    }
};
