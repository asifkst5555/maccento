<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProjectMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_project_id',
        'uploaded_by',
        'type',
        'delivery_stage',
        'disk',
        'path',
        'watermark_disk',
        'watermark_path',
        'watermark_signature',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function deliveryStage(): string
    {
        return (string) ($this->delivery_stage ?: ($this->type === 'final_zip' ? 'final_zip' : 'raw'));
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}