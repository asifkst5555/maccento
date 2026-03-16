<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencySetting extends Model
{
    protected $fillable = [
        'default_currency',
        'enabled_currencies',
    ];

    protected $casts = [
        'enabled_currencies' => 'array',
    ];
}
