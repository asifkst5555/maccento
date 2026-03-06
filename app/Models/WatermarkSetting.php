<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WatermarkSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'logo_disk',
        'logo_path',
        'position',
        'size',
        'opacity_percent',
    ];
}
