<?php

namespace App\Jobs;

use App\Http\Controllers\DashboardController;
use App\Models\ClientProjectMedia;
use App\Models\DropboxImportSession;
use App\Services\DropboxProvider;
use App\Services\PanelNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CloudImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $sessionId;
    public int $tries = 3;
    public int $timeout = 600; // 10 minutes maximum execution time per batch try

    public function __construct(int $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function handle(): void
    {
        $session = DropboxImportSession::find($this->sessionId);
        if (!$session) {
            return;
        }

        // Cancelled check
        if ($session->status === 'cancelled') {
            return;
        }

        $session->status = 'importing';
        if (blank($session->started_at)) {
            $session->started_at = now();
        }
        $session->save();

        $project = $session->project;
        $client = $project?->client;
        $user = $session->user;

        // Send Import Started Notification
        $this->sendMilestoneNotifications($session, 'started');

        // Resolve DropboxProvider dynamically
        $provider = app(DropboxProvider::class);
        $dashboardController = app(DashboardController::class);

        $files = $session->files_queue ?? [];
        $totalFiles = count($files);

        $processedCount = $session->processed_files;
        $importedCount = $session->imported_files;
        $duplicateCount = $session->duplicate_files;
        $failedCount = $session->failed_files;

        $duplicateReport = $session->duplicate_report ?? [];
        $errorLog = $session->error_log ?? [];

        $mediaDisk = $dashboardController->resolveMediaDisk();
        $projectMediaBasePath = $dashboardController->projectMediaBasePath($project);

        for ($i = 0; $i < $totalFiles; $i++) {
            // Check cancellation status in real-time
            $session->refresh();
            if ($session->status === 'cancelled') {
                $this->sendMilestoneNotifications($session, 'cancelled');
                return;
            }

            $file = &$files[$i];

            // Only process pending or failed files (in case of resume / retry)
            if (($file['status'] ?? 'pending') !== 'pending' && ($file['status'] ?? '') !== 'failed') {
                continue;
            }

            $file['status'] = 'processing';
            $session->current_file = $file['name'];
            $session->files_queue = $files;
            $session->save();

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $tempLocalPath = sys_get_temp_dir() . '/dropbox_import_' . \Illuminate\Support\Str::random(16) . ($ext !== '' ? '.' . $ext : '');

            try {
                // Step 1: Download
                $downloaded = $provider->download($session->folder_url, $file['id'], $file['path'], $tempLocalPath);
                if (!$downloaded || !file_exists($tempLocalPath) || filesize($tempLocalPath) === 0) {
                    throw new \Exception('Failed to download file stream from Dropbox.');
                }

                $actualSize = filesize($tempLocalPath);
                $fileHash = hash_file('sha256', $tempLocalPath);

                // Step 2: Phase 2 Duplicate Check using Hash
                $duplicate = ClientProjectMedia::query()
                    ->where('client_project_id', $project->id)
                    ->where(function ($query) use ($file, $fileHash, $actualSize) {
                        $query->where('dropbox_file_id', $file['id'])
                            ->orWhere('file_hash', $fileHash)
                            ->orWhere(function ($q) use ($file, $actualSize) {
                                $q->where('original_name', $file['name'])
                                  ->where('size_bytes', $actualSize);
                            });
                    })
                    ->first();

                if ($duplicate) {
                    $reason = 'Content hash or duplicate filename already exists in the project.';
                    $duplicateReport[] = [
                        'dropbox_id' => $file['id'],
                        'filename' => $file['name'],
                        'sha256' => $fileHash,
                        'reason' => $reason,
                    ];
                    $file['status'] = 'skipped';
                    $duplicateCount++;
                    $processedCount++;
                    
                    $session->processed_files = $processedCount;
                    $session->duplicate_files = $duplicateCount;
                    $session->duplicate_report = $duplicateReport;
                    $session->files_queue = $files;
                    $session->save();

                    @unlink($tempLocalPath);
                    continue;
                }

                // Step 3: Security checks (Executable blocking)
                $mimeType = @mime_content_type($tempLocalPath) ?: '';
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                $executableMimes = [
                    'application/x-msdownload', 'application/x-sh', 'application/x-bash', 
                    'application/x-executable', 'text/x-php', 'text/x-python', 'text/x-javascript'
                ];
                $executableExts = ['exe', 'bat', 'sh', 'php', 'js', 'py', 'pl', 'cgi', 'cmd', 'msi', 'dll'];
                if (in_array($mimeType, $executableMimes, true) || in_array($ext, $executableExts, true)) {
                    throw new \Exception('Executable uploads are strictly prohibited for security reasons.');
                }

                // Step 4: Map extension & stage
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

                // Target Bucket Folder mapping
                $targetBucket = $dashboardController->projectMediaBucketForStage($mediaStage);
                if ($finalType === 'raw_zip') {
                    $targetBucket = 'raw-zip';
                } elseif ($finalType === 'final_zip') {
                    $targetBucket = 'delivery';
                }

                $galleryUploadPath = $dashboardController->projectMediaUploadPath($project, $user, $targetBucket);

                // Save to Storage
                $storedName = \Illuminate\Support\Str::random(40) . ($ext !== '' ? '.' . $ext : '');
                $storedPath = Storage::disk($mediaDisk)->putFileAs($galleryUploadPath, new \Illuminate\Http\File($tempLocalPath), $storedName);
                if (!$storedPath) {
                    throw new \Exception('Failed to write file to system storage.');
                }

                // Determine Mime Type fallback
                if ($mimeType === '') {
                    if ($finalType === 'image') $mimeType = 'image/jpeg';
                    elseif ($finalType === 'video') $mimeType = 'video/mp4';
                    elseif ($finalType === 'raw_zip' || $finalType === 'final_zip') $mimeType = 'application/zip';
                    else $mimeType = 'application/octet-stream';
                }

                // Generate Watermarks for Unpaid Image Galleries
                $watermarkDisk = null;
                $watermarkPath = null;
                $mediaWatermarkSignature = null;

                $isPaid = $project->invoices->contains(static fn ($inv): bool => $inv->status === 'paid');
                if ($finalType === 'image' && !$isPaid) {
                    $watermarkSettings = $dashboardController->getWatermarkSettings();
                    $watermarkRenderConfig = $dashboardController->resolveWatermarkRenderConfig($watermarkSettings);
                    $watermarkSignature = (string) ($watermarkRenderConfig['signature'] ?? '');

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

                // Stage column mapping
                $dbDeliveryStage = $mediaStage;
                if ($finalType === 'final_zip') {
                    $dbDeliveryStage = 'final_zip';
                }

                // Database registration
                $mediaItem = ClientProjectMedia::create([
                    'client_project_id' => $project->id,
                    'uploaded_by' => $session->user_id,
                    'type' => $finalType,
                    'delivery_stage' => $dbDeliveryStage,
                    'disk' => $mediaDisk,
                    'path' => $storedPath,
                    'watermark_disk' => $watermarkDisk,
                    'watermark_path' => $watermarkPath,
                    'watermark_signature' => $mediaWatermarkSignature,
                    'original_name' => $file['name'],
                    'mime_type' => $mimeType,
                    'size_bytes' => $actualSize,
                    'dropbox_file_id' => $file['id'],
                    'file_hash' => $fileHash,
                    'dropbox_shared_link' => $session->folder_url,
                    'import_source' => $session->provider,
                ]);

                // Extract EXIF EXIF metadata
                if ($finalType === 'image') {
                    try {
                        $metaDataArray = $dashboardController->extractMediaMetadata($tempLocalPath, $mimeType);
                        $mediaItem->metadata()->create($metaDataArray);
                    } catch (\Throwable $eex) {
                        Log::error("EXIF Extraction failed for file: " . $file['name'], ['error' => $eex->getMessage()]);
                    }
                }

                // Log Activity
                $stageLabel = $mediaStage === 'edited' ? 'edited/final media' : 'raw footage media';
                $dashboardController->logActivity(
                    request(),
                    'media',
                    $project->id,
                    $project->client_id,
                    $user,
                    'upload',
                    "Imported Dropbox file {$file['name']} ({$stageLabel}) to project: " . ($project->title ?: ('Project #' . $project->id)),
                    [
                        'stage' => $mediaStage,
                        'file_name' => $file['name'],
                        'dropbox_file_id' => $file['id'],
                    ]
                );

                $file['status'] = 'completed';
                $importedCount++;

            } catch (\Throwable $err) {
                Log::error("Dropbox Queue Import error for file: " . $file['name'], ['error' => $err->getMessage()]);
                $file['status'] = 'failed';
                $file['error'] = $err->getMessage();
                $errorLog[] = ['filename' => $file['name'], 'error' => $err->getMessage()];
                $failedCount++;
            }

            $processedCount++;

            // Update progress state dynamically
            $session->processed_files = $processedCount;
            $session->imported_files = $importedCount;
            $session->failed_files = $failedCount;
            $session->error_log = $errorLog;
            $session->files_queue = $files;
            $session->save();

            if ($tempLocalPath && file_exists($tempLocalPath)) {
                @unlink($tempLocalPath);
            }
        }

        // Completion
        $session->refresh();
        if ($session->status === 'importing') {
            $session->status = 'completed';
            $session->completed_at = now();
            $session->duration = (int) $session->completed_at->diffInSeconds($session->started_at);
            $session->save();

            // Log activity complete
            $userRole = $user?->role ?? 'photographer';
            $summaryMsg = "Completed Dropbox import of {$session->imported_files} file(s) (Duration: {$session->duration}s, Duplicates: {$session->duplicate_files}, Failures: {$session->failed_files})";
            
            if (\Schema::hasTable('request_edit_logs')) {
                \App\Models\RequestEditLog::create([
                    'request_type' => 'media',
                    'request_id' => $session->client_project_id,
                    'entity_type' => 'media',
                    'entity_id' => $session->client_project_id,
                    'client_id' => $project->client_id,
                    'actor_user_id' => $session->user_id,
                    'actor_role' => $userRole,
                    'action' => 'import',
                    'summary' => $summaryMsg,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'changes' => [
                        'folder_url' => $session->folder_url,
                        'imported_count' => $session->imported_files,
                        'duplicate_count' => $session->duplicate_files,
                        'failed_count' => $session->failed_files,
                        'total_files' => $session->total_files,
                    ],
                ]);
            }

            // Send completed notifications
            $this->sendMilestoneNotifications($session, 'completed');
        }
    }

    public function failed(\Throwable $exception): void
    {
        $session = DropboxImportSession::find($this->sessionId);
        if ($session) {
            $session->status = 'failed';
            $errorLog = $session->error_log ?? [];
            $errorLog[] = ['filename' => 'System Worker', 'error' => $exception->getMessage()];
            $session->error_log = $errorLog;
            $session->save();

            // Send failed notification
            $this->sendMilestoneNotifications($session, 'failed');
        }
    }

    private function sendMilestoneNotifications(DropboxImportSession $session, string $milestone): void
    {
        try {
            $project = $session->project;
            if (!$project) return;

            $client = $project->client;
            $notifyService = app(PanelNotificationService::class);

            $title = "Dropbox Import " . ucfirst($milestone);
            $actionUrl = route('admin.media-delivery.index') . '#session-' . $session->uuid;

            switch ($milestone) {
                case 'started':
                    $body = "Import started for project '{$project->title}' from Dropbox.";
                    break;
                case 'completed':
                    $body = "Import completed successfully! {$session->imported_files} file(s) imported, {$session->duplicate_files} duplicate(s) skipped.";
                    break;
                case 'failed':
                    $body = "Import failed for project '{$project->title}'. Check logs for details.";
                    break;
                case 'cancelled':
                    $body = "Import cancelled by administrator.";
                    break;
                default:
                    $body = "Dropbox import progress event: " . $milestone;
            }

            // 1. Notify Project Assignees (Admins/Photographers)
            $adminIds = \App\Models\User::query()
                ->whereIn('role', ['owner', 'admin', 'manager', 'photographer', 'editor'])
                ->pluck('id')
                ->all();
            
            $assigneeIds = $project->assignments->pluck('user_id')->all();
            $recipientIds = array_unique(array_merge($adminIds, $assigneeIds));

            foreach ($recipientIds as $recipientId) {
                $notifyService->notifyUser((int) $recipientId, 'dropbox_import_' . $milestone, $title, $body, $actionUrl, [
                    'session_uuid' => $session->uuid,
                    'project_id' => $project->id,
                ]);
            }

            // 2. Notify Client User
            if ($client && $client->user_id) {
                $clientActionUrl = route('user.projects.show', $project);
                $notifyService->notifyUser((int) $client->user_id, 'dropbox_import_' . $milestone, $title, $body, $clientActionUrl, [
                    'session_uuid' => $session->uuid,
                    'project_id' => $project->id,
                ]);
            }
        } catch (\Throwable $ex) {
            Log::error("Milestone notification dispatch failed: " . $ex->getMessage());
        }
    }
}
