<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_project_id',
        'created_by',
        'invoice_number',
        'amount',
        'amount_paid',
        'balance_due',
        'currency',
        'status',
        'issued_at',
        'due_date',
        'paid_at',
        'notes',
        'payment_provider',
        'payment_reference',
        'payment_method',
        'payment_meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'issued_at' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'payment_meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ClientInvoicePayment::class, 'client_invoice_id');
    }
}
