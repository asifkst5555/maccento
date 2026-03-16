<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'run_time',
        'run_days',
        'keep_count',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'run_days' => 'array',
        'keep_count' => 'integer',
    ];
}
