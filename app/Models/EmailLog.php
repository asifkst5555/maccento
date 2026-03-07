<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'mode',
        'template_key',
        'recipient_email',
        'reply_to',
        'cc',
        'bcc',
        'subject',
        'body_preview',
        'status',
        'error_message',
        'sent_at',
        'provider_message_id',
        'provider_status',
        'provider_last_event_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'provider_last_event_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sendgridEvents(): HasMany
    {
        return $this->hasMany(SendgridWebhookEvent::class, 'email_log_id');
    }
}
