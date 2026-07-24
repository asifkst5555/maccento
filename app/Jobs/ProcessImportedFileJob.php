<?php

namespace App\Jobs;

use App\Events\CloudImportProgressEvent;
use App\Http\Controllers\DashboardController;
use App\Models\ClientProjectMedia;
use App\Models\DropboxImportFileLog;
use App\Models\DropboxImportSession;
use App\Services\DropboxProvider;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessImportedFileJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $sessionId;
    public array $fileInfo;

    public function __construct(int $sessionId, array $fileInfo)
    {
        $this->sessionId = $sessionId;
        $this->fileInfo = $fileInfo;
    }

    public function handle(): void
    {
        // Check batch cancellation
        if ($this->batch()?->cancelled()) {
            return;
        }

        $session = DropboxImportSession::find($this->sessionId);
        if (!$session) {
            return;
        }

        $project = $session->project;
        $user = $session->user;
        if (!$project) {
            return;
        }

        $provider = app(DropboxProvider::class);
        $dashboardController = app(DashboardController::class);

        $ext = strtolower(pathinfo($this->fileInfo['name'], PATHINFO_EXTENSION));
        $tempLocalPath = sys_get_temp_dir() . '/cloud_import_' . \Illuminate\Support\Str::random(16) . ($ext !== '' ? '.' . $ext : '');

        try {
            // Step 1: Download stream
            $downloaded = $provider->download($session->folder_url, $this->fileInfo['id'], $this->fileInfo['path'], $tempLocalPath);
            if (!$downloaded || !file_exists($tempLocalPath) || filesize($tempLocalPath) === 0) {
                throw new \Exception('Failed to download file stream from Dropbox.');
            }

            $actualSize = filesize($tempLocalPath);
            $fileHash = hash_file('sha256', $tempLocalPath);
            $fileInfo = $this->fileInfo;

            // Step 2: Concurrency & Duplicate Check inside database transaction with lock
            $duplicateCheck = DB::transaction(function () use ($project, $fileInfo, $fileHash, $actualSize) {
                return ClientProjectMedia::query()
                    ->where('client_project_id', $project->id)
                    ->where(function ($query) use ($fileInfo, $fileHash, $actualSize) {
                        $query->where('dropbox_file_id', $fileInfo['id'])
                            ->orWhere('file_hash', $fileHash)
                            ->orWhere(function ($q) use ($fileInfo, $actualSize) {
                                $q->where('original_name', $fileInfo['name'])
                                  ->where('size_bytes', $actualSize);
                            });
                    })
                    ->lockForUpdate() // concurrency race-condition block
                    ->first();
            });

            if ($duplicateCheck) {
                $this->logDuplicate($session, $this->fileInfo['name'], $this->fileInfo['id'], $fileHash, 'File content hash or name duplicate exists.');
                @unlink($tempLocalPath);
                return;
            }

            // Step 3: Security checks (Executable blocking)
            $mimeType = @mime_content_type($tempLocalPath) ?: '';
            $ext = strtolower(pathinfo($this->fileInfo['name'], PATHINFO_EXTENSION));

            $executableMimes = [
                'application/x-msdownload', 'application/x-sh', 'application/x-bash', 
                'application/x-executable', 'text/x-php', 'text/x-python', 'text/x-javascript'
            ];
            $executableExts = ['exe', 'bat', 'sh', 'php', 'js', 'py', 'pl', 'cgi', 'cmd', 'msi', 'dll'];
            if (in_array($mimeType, $executableMimes, true) || in_array($ext, $executableExts, true)) {
                throw new \Exception('Executable uploads are prohibited.');
            }

            // Step 4: Storage Stage & Path Resolution
            $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'tiff', 'bmp'];
            $videoExts = ['mp4', 'mov', 'avi', 'mkv'];

            $finalType = 'other';
            if (in_array($ext, $imageExts, true)) {
                $finalType = 'image';
            } elseif (in_array($ext, $videoExts, true)) {
                $finalType = 'video';
            } elseif ($ext === 'zip') {
                $finalType = ($session->media_stage === 'raw') ? 'raw_zip' : 'final_zip';
            }

            $mediaStage = ($session->media_stage === 'raw') ? 'raw' : 'edited';
            $targetBucket = $dashboardController->projectMediaBucketForStage($mediaStage);
            if ($finalType === 'raw_zip') {
                $targetBucket = 'raw-zip';
            } elseif ($finalType === 'final_zip') {
                $targetBucket = 'delivery';
            }

            $galleryUploadPath = $dashboardController->projectMediaUploadPath($project, $user, $targetBucket);

            // Preserve folder structure (Feature 7)
            $relativePath = trim(dirname($this->fileInfo['path']), '/.');
            if ($relativePath !== '') {
                $galleryUploadPath .= '/' . $relativePath;
            }

            $mediaDisk = $dashboardController->resolveMediaDisk();
            $storedName = Str::random(40) . ($ext !== '' ? '.' . $ext : '');
            $storedPath = Storage::disk($mediaDisk)->putFileAs($galleryUploadPath, new \Illuminate\Http\File($tempLocalPath), $storedName);
            if (!$storedPath) {
                throw new \Exception('Failed to write file to storage.');
            }

            if ($mimeType === '') {
                if ($finalType === 'image') $mimeType = 'image/jpeg';
                elseif ($finalType === 'video') $mimeType = 'video/mp4';
                elseif ($finalType === 'raw_zip' || $finalType === 'final_zip') $mimeType = 'application/zip';
                else $mimeType = 'application/octet-stream';
            }

            // Unpaid Watermarks
            $watermarkDisk = null;
            $watermarkPath = null;
            $mediaWatermarkSignature = null;

            $isPaid = $project->invoices->contains(static fn ($inv): bool => $inv->status === 'paid');
            if ($finalType === 'image' && !$isPaid) {
                $watermarkSettings = $dashboardController->getWatermarkSettings();
                $watermarkRenderConfig = $dashboardController->resolveWatermarkRenderConfig($watermarkSettings);
                $watermarkSignature = (string) ($watermarkRenderConfig['signature'] ?? '');

                $projectMediaBasePath = $dashboardController->projectMediaBasePath($project);
                $watermarked = $dashboardController->generateHardWatermarkVariant($mediaDisk, $storedPath, $projectMediaBasePath, $watermarkRenderConfig);
                if (is_array($watermarked)) {
                    $watermarkDisk = (string) ($watermarked['disk'] ?? 'public');
                    $watermarkPath = (string) ($watermarked['path'] ?? '');
                    if ($watermarkPath !== '') {
                        $mediaWatermarkSignature = $watermarkSignature;
                    } else {
                        $watermarkDisk = null;
                        $watermarkPath = null;
                    }
                }
            }

            $dbDeliveryStage = $mediaStage;
            if ($finalType === 'final_zip') {
                $dbDeliveryStage = 'final_zip';
            }

            // Register media with folder_path
            $mediaItem = DB::transaction(function () use ($project, $session, $finalType, $dbDeliveryStage, $mediaDisk, $storedPath, $relativePath, $watermarkDisk, $watermarkPath, $mediaWatermarkSignature, $actualSize, $fileHash, $mimeType) {
                return ClientProjectMedia::create([
                    'client_project_id' => $project->id,
                    'uploaded_by' => $session->user_id,
                    'type' => $finalType,
                    'delivery_stage' => $dbDeliveryStage,
                    'disk' => $mediaDisk,
                    'path' => $storedPath,
                    'folder_path' => $relativePath !== '' ? $relativePath : null,
                    'watermark_disk' => $watermarkDisk,
                    'watermark_path' => $watermarkPath,
                    'watermark_signature' => $mediaWatermarkSignature,
                    'original_name' => $this->fileInfo['name'],
                    'mime_type' => $mimeType,
                    'size_bytes' => $actualSize,
                    'dropbox_file_id' => $this->fileInfo['id'],
                    'file_hash' => $fileHash,
                    'dropbox_shared_link' => $session->folder_url,
                    'import_source' => $session->provider,
                ]);
            });

            // Extract EXIF Metadata
            if ($finalType === 'image') {
                try {
                    $metaDataArray = $dashboardController->extractMediaMetadata($tempLocalPath, $mimeType);
                    $mediaItem->metadata()->create($metaDataArray);
                } catch (\Throwable $eex) {
                    Log::error("EXIF failed for: " . $this->fileInfo['name'], ['error' => $eex->getMessage()]);
                }
            }

            // Log activity audit
            $stageLabel = $mediaStage === 'edited' ? 'edited/final media' : 'raw footage media';
            $dashboardController->logActivity(
                request(),
                'media',
                $project->id,
                $project->client_id,
                $user,
                'upload',
                "Imported Dropbox file {$this->fileInfo['name']} ({$stageLabel}) to project: " . ($project->title ?: ('Project #' . $project->id)),
                [
                    'stage' => $mediaStage,
                    'file_name' => $this->fileInfo['name'],
                    'dropbox_file_id' => $this->fileInfo['id'],
                    'batch_id' => $this->batch()?->id,
                ]
            );

            // Log file success
            DropboxImportFileLog::create([
                'dropbox_import_session_id' => $session->id,
                'filename' => $this->fileInfo['name'],
                'dropbox_file_id' => $this->fileInfo['id'],
                'status' => 'completed',
                'file_size' => $actualSize,
                'file_hash' => $fileHash,
            ]);

            $session->increment('processed_files');
            $session->increment('imported_files');
            $session->update(['current_file' => $this->fileInfo['name']]);

            // Broadcast progress (Feature 3 & 8)
            $this->broadcastProgress($session, $mediaItem);

        } catch (\Throwable $err) {
            Log::error("ProcessImportedFileJob error: " . $this->fileInfo['name'], ['error' => $err->getMessage()]);
            $this->logFailure($session, $this->fileInfo['name'], $err->getMessage());
        } finally {
            if ($tempLocalPath && file_exists($tempLocalPath)) {
                @unlink($tempLocalPath);
            }
        }
    }

    private function logDuplicate(DropboxImportSession $session, string $filename, string $fileId, string $hash, string $reason): void
    {
        DropboxImportFileLog::create([
            'dropbox_import_session_id' => $session->id,
            'filename' => $filename,
            'dropbox_file_id' => $fileId,
            'status' => 'skipped',
            'error_message' => $reason,
            'file_hash' => $hash,
        ]);

        $session->increment('processed_files');
        $session->increment('duplicate_files');

        // Append to session duplicate report JSON array (for backwards CSV export compatibility)
        $dupReport = $session->duplicate_report ?? [];
        $dupReport[] = [
            'dropbox_id' => $fileId,
            'filename' => $filename,
            'sha256' => $hash,
            'reason' => $reason,
        ];
        $session->update(['duplicate_report' => $dupReport]);

        $this->broadcastProgress($session);
    }

    private function logFailure(DropboxImportSession $session, string $filename, string $error): void
    {
        DropboxImportFileLog::create([
            'dropbox_import_session_id' => $session->id,
            'filename' => $filename,
            'dropbox_file_id' => $this->fileInfo['id'] ?? null,
            'status' => 'failed',
            'error_message' => $error,
        ]);

        $session->increment('processed_files');
        $session->increment('failed_files');

        // Append to error log array
        $errLog = $session->error_log ?? [];
        $errLog[] = ['filename' => $filename, 'error' => $error];
        $session->update(['error_log' => $errLog]);

        $this->broadcastProgress($session);
    }

    private function broadcastProgress(DropboxImportSession $session, ?ClientProjectMedia $mediaItem = null): void
    {
        $session->refresh();
        $total = $session->total_files;
        $processed = $session->processed_files;
        $percent = $total > 0 ? Math_round_pct($processed, $total) : 0;

        // Calculate speed & ETA
        $speed = 0;
        $eta = 0;
        if ($session->started_at) {
            $elapsed = max(1, now()->diffInSeconds($session->started_at));
            // Gather bytes processed by sum file sizes of logs
            $processedBytes = DropboxImportFileLog::where('dropbox_import_session_id', $session->id)
                ->where('status', 'completed')
                ->sum('file_size');
            $speed = round($processedBytes / $elapsed); // bytes/sec
            $remainingBytes = max(0, $session->total_size - $processedBytes);
            if ($speed > 0) {
                $eta = (int) round($remainingBytes / $speed);
            }
        }

        $mediaData = null;
        if ($mediaItem) {
            $mediaData = [
                'id' => $mediaItem->id,
                'type' => strtoupper($mediaItem->type),
                'original_name' => $mediaItem->original_name,
                'uploader_name' => $mediaItem->uploader?->name ?: 'System',
                'uploader_role' => $mediaItem->uploader?->role ? ucfirst($mediaItem->uploader->role) : '',
                'view_url' => route('admin.projects.media.view', ['project' => $session->client_project_id, 'media' => $mediaItem]),
                'delete_url' => route('admin.projects.media.delete', ['project' => $session->client_project_id, 'media' => $mediaItem]),
                'stage' => $mediaItem->delivery_stage,
            ];
        }

        event(new CloudImportProgressEvent($session->uuid, [
            'status' => $session->status,
            'total_files' => $total,
            'processed_files' => $processed,
            'imported_files' => $session->imported_files,
            'duplicate_files' => $session->duplicate_files,
            'failed_files' => $session->failed_files,
            'current_file' => $session->current_file,
            'percent' => $percent,
            'speed_bytes_per_sec' => $speed,
            'estimated_remaining_seconds' => $eta,
            'new_media' => $mediaData,
        ]));
    }
}

// Helper pct calculator function
function Math_round_pct($processed, $total) {
    return (int) round(($processed / $total) * 100);
}
