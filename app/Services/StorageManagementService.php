<?php

namespace App\Services;

use App\Models\ClientProjectMedia;
use App\Models\ClientProject;
use App\Models\Client;
use App\Models\User;

class StorageManagementService
{
    // Storage Quotas in Bytes
    public const DEFAULT_PROJECT_QUOTA = 10737418240;      // 10 GB
    public const DEFAULT_CLIENT_QUOTA = 53687091200;       // 50 GB
    public const DEFAULT_PHOTOGRAPHER_QUOTA = 107374182400; // 100 GB

    /**
     * Get storage consumed by a project in bytes
     */
    public function getProjectStorageUsage(int $projectId): int
    {
        return (int) ClientProjectMedia::where('client_project_id', $projectId)->sum('size_bytes');
    }

    /**
     * Get storage consumed by a client in bytes
     */
    public function getClientStorageUsage(int $clientId): int
    {
        return (int) ClientProjectMedia::whereHas('project', function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })->sum('size_bytes');
    }

    /**
     * Get storage consumed by a photographer in bytes
     */
    public function getPhotographerStorageUsage(int $userId): int
    {
        return (int) ClientProjectMedia::where('uploaded_by', $userId)->sum('size_bytes');
    }

    /**
     * Verify project storage limits
     */
    public function hasAvailableQuotaForProject(int $projectId, int $additionalBytes): bool
    {
        $limit = self::DEFAULT_PROJECT_QUOTA;
        $used = $this->getProjectStorageUsage($projectId);

        return ($used + $additionalBytes) <= $limit;
    }

    /**
     * Verify client storage limits
     */
    public function hasAvailableQuotaForClient(int $clientId, int $additionalBytes): bool
    {
        $limit = self::DEFAULT_CLIENT_QUOTA;
        $used = $this->getClientStorageUsage($clientId);

        return ($used + $additionalBytes) <= $limit;
    }

    /**
     * Verify photographer storage limits
     */
    public function hasAvailableQuotaForPhotographer(int $userId, int $additionalBytes): bool
    {
        $limit = self::DEFAULT_PHOTOGRAPHER_QUOTA;
        $used = $this->getPhotographerStorageUsage($userId);

        return ($used + $additionalBytes) <= $limit;
    }
}
