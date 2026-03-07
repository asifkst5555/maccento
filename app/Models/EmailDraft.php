<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'recipient_email',
        'reply_to',
        'cc',
        'bcc',
        'client_project_id',
        'subject',
        'message',
        'status',
        'last_opened_at',
    ];

    protected $casts = [
        'last_opened_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }
}
