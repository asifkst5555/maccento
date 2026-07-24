<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'login_at',
        'logout_at',
        'ip_address',
        'user_agent',
        'browser',
        'operating_system',
        'device',
        'is_success',
        'failure_reason',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'is_success' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parse User Agent helper.
     */
    public static function parseUserAgent(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return ['browser' => 'Unknown', 'operating_system' => 'Unknown', 'device' => 'Desktop'];
        }

        $browser = 'Unknown Browser';
        $os = 'Unknown OS';
        $device = 'Desktop';

        // Browser
        if (str_contains($userAgent, 'MSIE') && !str_contains($userAgent, 'Opera')) {
            $browser = 'Internet Explorer';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Opera')) {
            $browser = 'Opera';
        } elseif (str_contains($userAgent, 'Edge') || str_contains($userAgent, 'Edg')) {
            $browser = 'Edge';
        }

        // OS
        $lcUa = strtolower($userAgent);
        if (str_contains($lcUa, 'windows') || str_contains($lcUa, 'win32')) {
            $os = 'Windows';
        } elseif (str_contains($lcUa, 'macintosh') || str_contains($lcUa, 'mac os x')) {
            $os = 'Mac OS X';
        } elseif (str_contains($lcUa, 'android')) {
            $os = 'Android';
            $device = 'Mobile';
        } elseif (str_contains($lcUa, 'iphone') || str_contains($lcUa, 'ipad') || str_contains($lcUa, 'ipod')) {
            $os = 'iOS';
            $device = str_contains($lcUa, 'ipad') ? 'Tablet' : 'Mobile';
        } elseif (str_contains($lcUa, 'ubuntu')) {
            $os = 'Ubuntu Linux';
        } elseif (str_contains($lcUa, 'linux')) {
            $os = 'Linux';
        }

        if ($device === 'Desktop' && (str_contains($lcUa, 'mobile') || str_contains($lcUa, 'phone'))) {
            $device = 'Mobile';
        } elseif ($device === 'Desktop' && (str_contains($lcUa, 'tablet') || str_contains($lcUa, 'ipad'))) {
            $device = 'Tablet';
        }

        return ['browser' => $browser, 'operating_system' => $os, 'device' => $device];
    }
}
