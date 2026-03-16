<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_project_id',
        'lead_profile_id',
        'requester_user_id',
        'requested_service',
        'preferred_date',
        'preferred_time_window',
        'alternate_slots',
        'notes',
        'status',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'alternate_slots' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function leadProfile(): BelongsTo
    {
        return $this->belongsTo(LeadProfile::class, 'lead_profile_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }
}
