<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_service_requests') || Schema::hasColumn('client_service_requests', 'client_project_id')) {
            return;
        }

        Schema::table('client_service_requests', function (Blueprint $table): void {
            $table->foreignId('client_project_id')->nullable()->after('client_id')->constrained('client_projects')->nullOnDelete();
            $table->index(['client_project_id', 'status']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_service_requests') || !Schema::hasColumn('client_service_requests', 'client_project_id')) {
            return;
        }

        Schema::table('client_service_requests', function (Blueprint $table): void {
            $table->dropIndex('client_service_requests_client_project_id_status_index');

            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->dropColumn('client_project_id');
                return;
            }

            $table->dropConstrainedForeignId('client_project_id');
        });
    }
};
