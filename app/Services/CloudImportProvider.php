<?php

namespace App\Services;

interface CloudImportProvider
{
    /**
     * Scan folder link and return file collection + counts
     * 
     * @param string $folderUrl
     * @return array{files: array, counts: array}
     */
    public function scan(string $folderUrl): array;

    /**
     * Stream file download to local temporary path
     * 
     * @param string $folderUrl
     * @param string $fileId
     * @param string $filePath
     * @param string $tempLocalPath
     * @return bool
     */
    public function download(string $folderUrl, string $fileId, string $filePath, string $tempLocalPath): bool;
}
