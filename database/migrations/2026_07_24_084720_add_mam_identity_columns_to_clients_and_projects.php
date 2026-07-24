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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('client_code', 50)->nullable()->unique()->after('id');
        });

        Schema::table('client_projects', function (Blueprint $table) {
            $table->string('project_code', 50)->nullable()->unique()->after('id');
        });

        // Backfill existing clients
        $clients = \DB::table('clients')->get();
        foreach ($clients as $client) {
            $clientCode = 'CL' . str_pad((string) $client->id, 6, '0', STR_PAD_LEFT);
            $slugName = \Illuminate\Support\Str::snake($client->name ?: 'client');
            $folderName = strtolower($clientCode . '_' . $slugName);
            \DB::table('clients')->where('id', $client->id)->update([
                'client_code' => $clientCode,
                'folder_name' => $folderName,
            ]);
        }

        // Backfill existing projects
        $projects = \DB::table('client_projects')->get();
        foreach ($projects as $project) {
            $projectCode = 'PR' . str_pad((string) $project->id, 6, '0', STR_PAD_LEFT);
            $slugTitle = \Illuminate\Support\Str::snake($project->title ?: 'project');
            $folderName = strtolower($projectCode . '_' . $slugTitle);
            \DB::table('client_projects')->where('id', $project->id)->update([
                'project_code' => $projectCode,
                'folder_name' => $folderName,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('client_code');
        });

        Schema::table('client_projects', function (Blueprint $table) {
            $table->dropColumn('project_code');
        });
    }
};
