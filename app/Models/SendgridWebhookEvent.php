<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendgridWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_log_id',
        'event_type',
        'email',
        'sg_message_id',
        'sg_event_id',
        'category',
        'payload',
        'occurred_at',
        'processed_at',
    ];

    protected $casts = [
        'category' => 'array',
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class, 'email_log_id');
    }
}
