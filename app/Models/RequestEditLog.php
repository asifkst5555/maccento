<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestEditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_type',
        'request_id',
        'entity_type',
        'entity_id',
        'client_id',
        'actor_user_id',
        'actor_role',
        'action',
        'summary',
        'ip_address',
        'user_agent',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
