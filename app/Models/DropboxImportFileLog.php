<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DropboxImportFileLog extends Model
{
    use HasFactory;

    protected $table = 'dropbox_import_file_logs';

    protected $fillable = [
        'dropbox_import_session_id',
        'filename',
        'dropbox_file_id',
        'status',
        'error_message',
        'file_size',
        'file_hash',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(DropboxImportSession::class, 'dropbox_import_session_id');
    }
}
