<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_by',
        'name',
        'client_code',
        'folder_name',
        'email',
        'phone',
        'company',
        'status',
        'notes',
        'notify_portal',
        'notify_invoice_email',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($client) {
            $client->client_code = 'CL_TEMP_' . \Illuminate\Support\Str::random(10);
            $client->folder_name = 'cl_temp_' . \Illuminate\Support\Str::random(10);
        });
        static::created(function ($client) {
            $client->client_code = 'CL' . str_pad((string) $client->id, 6, '0', STR_PAD_LEFT);
            $slugName = \Illuminate\Support\Str::snake($client->name ?: 'client');
            $client->folder_name = strtolower($client->client_code . '_' . $slugName);
            $client->saveQuietly();
        });
    }

    protected $casts = [
        'notify_portal' => 'boolean',
        'notify_invoice_email' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(ClientProject::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(ClientInvoice::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ClientMessage::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ClientServiceRequest::class);
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class);
    }
}
