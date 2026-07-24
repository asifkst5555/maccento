<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DropboxProvider implements CloudImportProvider
{
    /**
     * Scan folder link and return file collection + counts
     */
    public function scan(string $folderUrl): array
    {
        $settings = \App\Models\CloudProviderSetting::where('provider', 'dropbox')->where('is_active', true)->first();
        $token = $settings && !empty($settings->access_token) ? trim((string) $settings->access_token) : trim((string) config('services.dropbox.access_token'));
        if ($token === '') {
            throw new \Exception('Dropbox Integration is not configured. Please define DROPBOX_ACCESS_TOKEN.');
        }

        $allFiles = [];
        $hasMore = true;
        $cursor = null;
        $loopLimit = 20; // Safeguard against massive pagination loops
        $loopCount = 0;

        $imagesCount = 0;
        $videosCount = 0;
        $documentsCount = 0;
        $unsupportedCount = 0;

        while ($hasMore && $loopCount < $loopLimit) {
            $loopCount++;
            if ($cursor === null) {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                ])
                ->timeout(20)
                ->post('https://api.dropboxapi.com/2/sharing/list_shared_link_files', [
                    'url' => $folderUrl,
                    'limit' => 100,
                ]);
            } else {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                ])
                ->timeout(20)
                ->post('https://api.dropboxapi.com/2/sharing/list_shared_link_files/continue', [
                    'cursor' => $cursor,
                ]);
            }

            if ($response->failed()) {
                $status = $response->status();
                $err = $response->json('error_summary') ?: $response->body();
                throw new \Exception("Dropbox scanning failed (status {$status}): {$err}");
            }

            $data = $response->json();
            $entries = $data['entries'] ?? [];
            
            foreach ($entries as $entry) {
                if (($entry['.tag'] ?? '') !== 'file') {
                    continue;
                }

                $name = (string) ($entry['name'] ?? '');
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'tiff', 'bmp'];
                $videoExts = ['mp4', 'mov', 'avi', 'mkv'];
                $docExts = ['pdf', 'zip'];

                if (in_array($ext, $imageExts, true)) {
                    $type = 'image';
                    $imagesCount++;
                } elseif (in_array($ext, $videoExts, true)) {
                    $type = 'video';
                    $videosCount++;
                } elseif (in_array($ext, $docExts, true)) {
                    $type = 'document';
                    $documentsCount++;
                } else {
                    $unsupportedCount++;
                    continue;
                }

                $fileId = (string) ($entry['id'] ?? '');
                $size = (int) ($entry['size'] ?? 0);
                $path = (string) ($entry['path_lower'] ?? $entry['path_display'] ?? '/' . $name);

                $allFiles[] = [
                    'id' => $fileId,
                    'name' => $name,
                    'path' => $path,
                    'size' => $size,
                    'type' => $type,
                ];
            }

            $cursor = $data['cursor'] ?? null;
            $hasMore = (bool) ($data['has_more'] ?? false) && ($cursor !== null);
        }

        return [
            'files' => $allFiles,
            'counts' => [
                'images' => $imagesCount,
                'videos' => $videosCount,
                'documents' => $documentsCount,
                'unsupported' => $unsupportedCount,
            ]
        ];
    }

    /**
     * Stream file download to local temporary path
     */
    public function download(string $folderUrl, string $fileId, string $filePath, string $tempLocalPath): bool
    {
        $settings = \App\Models\CloudProviderSetting::where('provider', 'dropbox')->where('is_active', true)->first();
        $token = $settings && !empty($settings->access_token) ? trim((string) $settings->access_token) : trim((string) config('services.dropbox.access_token'));
        if ($token === '') {
            throw new \Exception('Dropbox access token is not configured.');
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Dropbox-API-Arg' => json_encode([
                'url' => $folderUrl,
                'path' => $filePath,
            ]),
        ])
        ->withOptions([
            'sink' => $tempLocalPath,
        ])
        ->timeout(180)
        ->post('https://content.dropboxapi.com/2/sharing/get_shared_link_file');

        return $response->successful() && file_exists($tempLocalPath) && filesize($tempLocalPath) > 0;
    }
}
