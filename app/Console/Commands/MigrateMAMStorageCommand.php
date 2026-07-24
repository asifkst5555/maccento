<?php

namespace App\Console\Commands;

use App\Models\ClientProjectMedia;
use App\Http\Controllers\DashboardController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateMAMStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-import:migrate-storage {--dry-run : Perform a dry run without moving files or updating database}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Migrate all legacy media storage folders into the new client-centric stage-based MAM structure.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting MAM storage architecture migration...');
        
        $mediaRecords = ClientProjectMedia::with(['project.client'])->get();
        $controller = new DashboardController();
        
        $migratedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($mediaRecords as $media) {
            $oldPath = $media->path;
            
            // Check if it's already using the new path structure
            if (str_starts_with($oldPath, 'clients/')) {
                $skippedCount++;
                continue;
            }

            $project = $media->project;
            if (!$project) {
                $this->warn("Skipping Media ID {$media->id}: No associated project found.");
                $skippedCount++;
                continue;
            }

            $client = $project->client;
            if (!$client) {
                $this->warn("Skipping Media ID {$media->id} (Project ID {$project->id}): Project has no associated client.");
                $skippedCount++;
                continue;
            }

            // Ensure MAM fields are set
            if (empty($client->folder_name) || empty($project->folder_name)) {
                $this->warn("Skipping Media ID {$media->id}: Client or project folder name is empty.");
                $skippedCount++;
                continue;
            }

            // Calculate old project base
            $projectTitle = trim((string) ($project->title ?? ''));
            $slug = Str::slug($projectTitle);
            if ($slug === '') {
                $slug = 'project';
            }
            $oldProjectBase = 'media/' . $slug . '-' . $project->id;

            if (!str_starts_with($oldPath, $oldProjectBase)) {
                $this->warn("Skipping Media ID {$media->id}: Path '{$oldPath}' does not match expected legacy base '{$oldProjectBase}'.");
                $skippedCount++;
                continue;
            }

            // Compute relative subpath (ignoring bucket name)
            $relativeTail = ltrim(substr($oldPath, strlen($oldProjectBase)), '/');
            $parts = explode('/', $relativeTail);
            if (count($parts) > 1) {
                array_shift($parts); // Remove old bucket name
                $subPath = implode('/', $parts);
            } else {
                $subPath = basename($oldPath);
            }

            // Mapped stage folder
            $stageFolder = $controller->getStorageStageFolder($media->deliveryStage());
            $newPath = 'clients/' . $client->folder_name . '/' . $project->folder_name . '/' . $stageFolder . '/' . $subPath;

            $this->line("Migrating Media ID {$media->id}: {$oldPath} -> {$newPath}");

            // Check if file exists in disk
            if (!Storage::disk($media->disk)->exists($oldPath)) {
                $this->error("Error: Physical file not found at '{$oldPath}' on disk '{$media->disk}'.");
                $errorCount++;
                continue;
            }

            // Check watermark path if exists
            $newWatermarkPath = null;
            if ($media->watermark_path) {
                $oldWatermarkPath = $media->watermark_path;
                if (!str_starts_with($oldWatermarkPath, 'clients/')) {
                    $newWatermarkPath = 'clients/' . $client->folder_name . '/' . $project->folder_name . '/08_watermarked/' . basename($oldWatermarkPath);
                    if (Storage::disk($media->watermark_disk ?: $media->disk)->exists($oldWatermarkPath)) {
                        $this->line("  Migrating Watermark: {$oldWatermarkPath} -> {$newWatermarkPath}");
                    } else {
                        $this->warn("  Warning: Physical watermark not found at '{$oldWatermarkPath}'.");
                    }
                }
            }

            if ($this->option('dry-run')) {
                $migratedCount++;
                continue;
            }

            // Execute move operations
            try {
                // Ensure target folder exists
                $targetDir = dirname(Storage::disk($media->disk)->path($newPath));
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }

                Storage::disk($media->disk)->move($oldPath, $newPath);

                if ($newWatermarkPath && Storage::disk($media->watermark_disk ?: $media->disk)->exists($oldWatermarkPath)) {
                    $watermarkDisk = $media->watermark_disk ?: $media->disk;
                    $targetWatermarkDir = dirname(Storage::disk($watermarkDisk)->path($newWatermarkPath));
                    if (!is_dir($targetWatermarkDir)) {
                        mkdir($targetWatermarkDir, 0775, true);
                    }
                    Storage::disk($watermarkDisk)->move($oldWatermarkPath, $newWatermarkPath);
                }

                // Update database
                $media->path = $newPath;
                if ($newWatermarkPath) {
                    $media->watermark_path = $newWatermarkPath;
                }
                $media->save();

                $migratedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to move files for Media ID {$media->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("Migration completed! Status: {$migratedCount} migrated, {$skippedCount} skipped, {$errorCount} errors.");
    }
}
