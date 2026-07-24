<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProjectMediaMetadata extends Model
{
    use HasFactory;

    protected $table = 'client_project_media_metadata';

    protected $fillable = [
        'client_project_media_id',
        'camera_make',
        'camera_model',
        'lens',
        'iso',
        'shutter_speed',
        'aperture',
        'capture_date',
        'gps_latitude',
        'gps_longitude',
        'width',
        'height',
    ];

    protected $casts = [
        'iso' => 'integer',
        'gps_latitude' => 'float',
        'gps_longitude' => 'float',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(ClientProjectMedia::class, 'client_project_media_id');
    }
}
