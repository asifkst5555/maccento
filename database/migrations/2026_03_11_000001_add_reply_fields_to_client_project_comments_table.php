<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_project_comments', function (Blueprint $table): void {
            $table->foreignId('parent_comment_id')
                ->nullable()
                ->after('client_project_id')
                ->constrained('client_project_comments')
                ->nullOnDelete();
            $table->timestamp('edited_at')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('client_project_comments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_comment_id');
            $table->dropColumn('edited_at');
        });
    }
};
