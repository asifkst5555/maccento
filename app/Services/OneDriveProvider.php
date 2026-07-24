<?php

namespace App\Services;

class OneDriveProvider implements CloudImportProvider
{
    public function scan(string $folderUrl): array
    {
        return [
            'files' => [],
            'counts' => ['images' => 0, 'videos' => 0, 'documents' => 0, 'unsupported' => 0]
        ];
    }

    public function download(string $folderUrl, string $fileId, string $filePath, string $tempLocalPath): bool
    {
        return false;
    }
}
