<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'lead_profile_id',
        'quote_build_id',
        'created_by',
        'title',
        'service_type',
        'property_address',
        'scheduled_at',
        'due_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function leadProfile(): BelongsTo
    {
        return $this->belongsTo(LeadProfile::class);
    }

    public function quoteBuild(): BelongsTo
    {
        return $this->belongsTo(QuoteBuild::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(ClientInvoice::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ClientMessage::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ClientProjectMedia::class);
    }

    public function isFullyPaid(): bool
    {
        return $this->invoices()->where('status', 'paid')->exists();
    }
}
