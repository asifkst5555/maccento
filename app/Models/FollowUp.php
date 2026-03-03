<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_profile_id',
        'owner_user_id',
        'method',
        'due_at',
        'status',
        'result_notes',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function leadProfile(): BelongsTo
    {
        return $this->belongsTo(LeadProfile::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
