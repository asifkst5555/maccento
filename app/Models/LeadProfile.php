<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'name',
        'email',
        'phone',
        'service_type',
        'property_type',
        'location',
        'budget_min',
        'budget_max',
        'timeline',
        'decision_maker',
        'preferred_contact',
        'notes',
        'score',
        'qualified_at',
        'status',
    ];

    protected $casts = [
        'qualified_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LeadEvent::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }
}
