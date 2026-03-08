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
    ];

    protected $casts = [
        'include_tax_on_pdf' => 'boolean',
        'tax_rate_percent' => 'decimal:2',
    ];
}
