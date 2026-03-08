<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_project_id',
        'requester_user_id',
        'requested_service',
        'subject',
        'details',
        'preferred_date',
        'status',
    ];

    protected $casts = [
        'preferred_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }
}
