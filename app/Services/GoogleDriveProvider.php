<?php

namespace App\Services;

class GoogleDriveProvider implements CloudImportProvider
{
    public function scan(string $folderUrl): array
    {
        // Placeholder for Google Drive API scanning
        return [
            'files' => [],
            'counts' => ['images' => 0, 'videos' => 0, 'documents' => 0, 'unsupported' => 0]
        ];
    }

    public function download(string $folderUrl, string $fileId, string $filePath, string $tempLocalPath): bool
    {
        // Placeholder for Google Drive API streaming file download
        return false;
    }
}
