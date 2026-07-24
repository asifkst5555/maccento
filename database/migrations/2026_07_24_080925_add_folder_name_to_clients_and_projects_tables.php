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
            $table->string('folder_name', 255)->nullable()->after('name');
        });

        Schema::table('client_projects', function (Blueprint $table) {
            $table->string('folder_name', 255)->nullable()->after('title');
        });

        // Backfill existing clients
        $clients = \DB::table('clients')->get();
        foreach ($clients as $client) {
            $slug = \Illuminate\Support\Str::slug($client->name ?: 'client');
            $folderName = ($slug !== '' ? $slug : 'client') . '_' . str_pad($client->id, 3, '0', STR_PAD_LEFT);
            \DB::table('clients')->where('id', $client->id)->update(['folder_name' => $folderName]);
        }

        // Backfill existing projects
        $projects = \DB::table('client_projects')->get();
        foreach ($projects as $project) {
            $slug = \Illuminate\Support\Str::slug($project->title ?: 'project');
            $folderName = ($slug !== '' ? $slug : 'project') . '_' . str_pad($project->id, 3, '0', STR_PAD_LEFT);
            \DB::table('client_projects')->where('id', $project->id)->update(['folder_name' => $folderName]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('folder_name');
        });

        Schema::table('client_projects', function (Blueprint $table) {
            $table->dropColumn('folder_name');
        });
    }
};
