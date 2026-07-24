<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CloudProviderSetting extends Model
{
    use HasFactory;

    protected $table = 'cloud_provider_settings';

    protected $fillable = [
        'provider',
        'is_active',
        'client_id',
        'client_secret',
        'access_token',
        'refresh_token',
        'expires_at',
        'additional_settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'additional_settings' => 'array',
    ];
}
