<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteFormSubmission extends Model
{
    protected $fillable = [
        'name',
        'company',
        'phone',
        'email',
        'service',
        'region',
        'message',
        'status',
        'source',
        'page_url',
        'ip_address',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];
}
