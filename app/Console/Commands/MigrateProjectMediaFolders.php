<?php

namespace App\Console\Commands;

use App\Models\ClientProject;
use App\Models\ClientProjectMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateProjectMediaFolders extends Command
{
    protected $signature = 'media:migrate-project-folders {--dry-run : Show planned changes without moving files or updating DB}';

    protected $description = 'Move legacy client-projects media paths into media/{project-name}-{id}/ structure and update DB references.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $moved = 0;
        $updated = 0;
        $skipped = 0;
        $missing = 0;
        $errors = 0;

        ClientProjectMedia::query()
            ->with('project:id,title')
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$moved, &$updated, &$skipped, &$missing, &$errors, $isDryRun): void {
                foreach ($items as $media) {
                    if (!$media instanceof ClientProjectMedia) {
                        continue;
                    }

                    $project = $media->project;
                    if (!$project instanceof ClientProject) {
                        $skipped++;
                        continue;
                    }

                    $basePath = $this->projectMediaBasePath($project);

                    try {
                        $originalResult = $this->migratePath(
                            (string) $media->disk,
                            (string) $media->path,
                            $basePath,
                            (int) $project->id,
                            $isDryRun
                        );

                        if ($originalResult['status'] === 'error') {
                            $errors++;
                            continue;
                        }

                        if ($originalResult['status'] === 'missing') {
                            $missing++;
                        }

                        if ($originalResult['status'] === 'moved') {
                            $moved++;
                        }

                        $newMediaPath = (string) ($originalResult['path'] ?? (string) $media->path);

                        $newWatermarkPath = (string) ($media->watermark_path ?? '');
                        if (!blank($media->watermark_disk) && !blank($media->watermark_path)) {
                            $watermarkResult = $this->migratePath(
                                (string) $media->watermark_disk,
                                (string) $media->watermark_path,
                                $basePath,
                                (int) $project->id,
                                $isDryRun,
                                true
                            );

                            if ($watermarkResult['status'] === 'error') {
                                $errors++;
                                continue;
                            }

                            if ($watermarkResult['status'] === 'missing') {
                                $missing++;
                            }

                            if ($watermarkResult['status'] === 'moved') {
                                $moved++;
                            }

                            $newWatermarkPath = (string) ($watermarkResult['path'] ?? (string) $media->watermark_path);
                        }

                        $changed = $newMediaPath !== (string) $media->path
                            || $newWatermarkPath !== (string) ($media->watermark_path ?? '');

                        if (!$changed) {
                            $skipped++;
                            continue;
                        }

                        if (!$isDryRun) {
                            $media->path = $newMediaPath;
                            if (!blank($media->watermark_disk) && !blank($media->watermark_path)) {
                                $media->watermark_path = $newWatermarkPath;
                            }
                            $media->save();
                        }

                        $updated++;
                    } catch (\Throwable $exception) {
                        report($exception);
                        $errors++;
                    }
                }
            });

        $this->newLine();
        $this->info($isDryRun ? 'Dry run complete.' : 'Migration complete.');
        $this->line('Moved files: ' . $moved);
        $this->line('Updated DB rows: ' . $updated);
        $this->line('Skipped rows: ' . $skipped);
        $this->line('Missing files: ' . $missing);
        $this->line('Errors: ' . $errors);

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{status:string,path:string}
     */
    private function migratePath(
        string $disk,
        string $sourcePath,
        string $projectBasePath,
        int $projectId,
        bool $isDryRun,
        bool $preferWatermarkSegment = false
    ): array {
        $sourcePath = trim($sourcePath);
        if ($sourcePath === '') {
            return ['status' => 'skip', 'path' => $sourcePath];
        }

        if (str_starts_with($sourcePath, trim($projectBasePath, '/') . '/')) {
            return ['status' => 'skip', 'path' => $sourcePath];
        }

        $segment = $this->resolveSegment($sourcePath, $projectId, $preferWatermarkSegment);
        $fileName = basename($sourcePath);
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return ['status' => 'skip', 'path' => $sourcePath];
        }

        $targetPath = trim($projectBasePath, '/') . '/' . $segment . '/' . $fileName;
        if ($targetPath === $sourcePath) {
            return ['status' => 'skip', 'path' => $sourcePath];
        }

        $storage = Storage::disk($disk);

        if (!$storage->exists($sourcePath)) {
            if ($storage->exists($targetPath)) {
                return ['status' => 'skip', 'path' => $targetPath];
            }

            return ['status' => 'missing', 'path' => $sourcePath];
        }

        $finalTargetPath = $targetPath;
        if ($storage->exists($finalTargetPath)) {
            $finalTargetPath = $this->resolveUniquePath($storage, $targetPath);
        }

        if (!$isDryRun) {
            $moved = $storage->move($sourcePath, $finalTargetPath);
            if (!$moved) {
                return ['status' => 'error', 'path' => $sourcePath];
            }
        }

        return ['status' => 'moved', 'path' => $finalTargetPath];
    }

    private function resolveSegment(string $path, int $projectId, bool $preferWatermarkSegment): string
    {
        $legacyPrefix = 'client-projects/' . $projectId . '/';
        if (str_starts_with($path, $legacyPrefix)) {
            $relative = substr($path, strlen($legacyPrefix));
            if (str_starts_with($relative, 'gallery-watermarked/')) {
                return 'gallery-watermarked';
            }

            if (str_starts_with($relative, 'delivery/')) {
                return 'delivery';
            }

            if (str_starts_with($relative, 'gallery/')) {
                return 'gallery';
            }
        }

        if (str_contains($path, '/gallery-watermarked/')) {
            return 'gallery-watermarked';
        }

        if (str_contains($path, '/delivery/')) {
            return 'delivery';
        }

        if (str_contains($path, '/gallery/')) {
            return 'gallery';
        }

        return $preferWatermarkSegment ? 'gallery-watermarked' : 'gallery';
    }

    private function resolveUniquePath($storage, string $targetPath): string
    {
        $dotPos = strrpos($targetPath, '.');
        $name = $dotPos === false ? $targetPath : substr($targetPath, 0, $dotPos);
        $ext = $dotPos === false ? '' : substr($targetPath, $dotPos);

        $counter = 1;
        $candidate = $targetPath;
        while ($storage->exists($candidate)) {
            $candidate = $name . '-' . $counter . $ext;
            $counter++;
        }

        return $candidate;
    }

    private function projectMediaBasePath(ClientProject $project): string
    {
        $projectTitle = trim((string) ($project->title ?? ''));
        $slug = Str::slug($projectTitle);
        if ($slug === '') {
            $slug = 'project';
        }

        return 'media/' . $slug . '-' . (int) $project->id;
    }
}
