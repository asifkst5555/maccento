<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_settings')) {
            return;
        }

        Schema::create('invoice_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('include_tax_on_pdf')->default(false);
            $table->decimal('tax_rate_percent', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};
