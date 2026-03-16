<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'include_tax_on_pdf',
        'tax_rate_percent',
        'stripe_enabled',
        'paypal_enabled',
        'manual_enabled',
        'manual_instructions',
        'auto_email_on_invoice_create',
        'reminder_enabled',
        'reminder_days_before',
        'reminder_send_on_due_date',
        'overdue_reminder_enabled',
        'overdue_reminder_every_days',
    ];

    protected $casts = [
        'include_tax_on_pdf' => 'boolean',
        'tax_rate_percent' => 'decimal:2',
        'stripe_enabled' => 'boolean',
        'paypal_enabled' => 'boolean',
        'manual_enabled' => 'boolean',
        'auto_email_on_invoice_create' => 'boolean',
        'reminder_enabled' => 'boolean',
        'reminder_days_before' => 'integer',
        'reminder_send_on_due_date' => 'boolean',
        'overdue_reminder_enabled' => 'boolean',
        'overdue_reminder_every_days' => 'integer',
    ];
}
