<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteBuild extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'user_id',
        'conversation_id',
        'lead_profile_id',
        'visitor_id',
        'status',
        'listing_type',
        'services',
        'options',
        'line_items',
        'estimated_total',
        'currency',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'services' => 'array',
        'options' => 'array',
        'line_items' => 'array',
        'submitted_at' => 'datetime',
    ];

    public static function makeQuoteId(): string
    {
        do {
            $id = 'Q-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        } while (self::query()->where('quote_id', $id)->exists());

        return $id;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function leadProfile(): BelongsTo
    {
        return $this->belongsTo(LeadProfile::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(QuoteEvent::class);
    }
}
