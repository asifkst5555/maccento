<?php

namespace App\Services;

use App\Models\PanelNotification;
use App\Models\User;

class PanelNotificationService
{
    /**
     * @param array<string,mixed> $data
     */
    public function notifyUser(int $userId, string $type, string $title, ?string $body = null, ?string $actionUrl = null, array $data = []): void
    {
        PanelNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'data' => $data,
            'read_at' => null,
        ]);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function notifyInternal(string $type, string $title, ?string $body = null, ?string $actionUrl = null, array $data = []): void
    {
        $ids = User::query()
            ->whereIn('role', ['owner', 'admin', 'manager', 'photographer', 'editor'])
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            $this->notifyUser((int) $id, $type, $title, $body, $actionUrl, $data);
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public function notifyByContact(?string $email, ?string $phone, string $type, string $title, ?string $body = null, ?string $actionUrl = null, array $data = []): void
    {
        if (blank($email) && blank($phone)) {
            return;
        }

        $ids = User::query()
            ->where(function ($query) use ($email, $phone): void {
                if (!blank($email)) {
                    $query->where('email', (string) $email);
                }
                if (!blank($phone)) {
                    if (!blank($email)) {
                        $query->orWhere('phone', (string) $phone);
                    } else {
                        $query->where('phone', (string) $phone);
                    }
                }
            })
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            $this->notifyUser((int) $id, $type, $title, $body, $actionUrl, $data);
        }
    }
}
