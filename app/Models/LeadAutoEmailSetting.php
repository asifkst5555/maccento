<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadAutoEmailSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'enabled',
        'tone',
        'template_prompt',
        'subject_prefix',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
