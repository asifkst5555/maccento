<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundWebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'url',
        'status',
        'response_code',
        'response_body',
        'payload',
        'error_message',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
    ];
}
