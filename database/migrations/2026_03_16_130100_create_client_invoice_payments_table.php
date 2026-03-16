<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('client_invoice_payments')) {
            return;
        }

        Schema::create('client_invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_invoice_id')->constrained('client_invoices')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('provider', 30)->nullable();
            $table->string('reference', 120)->nullable();
            $table->string('method', 30)->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index(['client_invoice_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_invoice_payments');
    }
};
