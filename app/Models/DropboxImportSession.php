<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DropboxImportSession extends Model
{
    use HasFactory;

    protected $table = 'dropbox_import_sessions';

    protected $fillable = [
        'uuid',
        'batch_id',
        'client_project_id',
        'user_id',
        'folder_url',
        'provider',
        'status',
        'started_at',
        'completed_at',
        'duration',
        'total_files',
        'processed_files',
        'imported_files',
        'duplicate_files',
        'failed_files',
        'total_size',
        'current_file',
        'media_stage',
        'duplicate_report',
        'error_log',
        'files_queue',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_files' => 'integer',
        'processed_files' => 'integer',
        'imported_files' => 'integer',
        'duplicate_files' => 'integer',
        'failed_files' => 'integer',
        'total_size' => 'integer',
        'duplicate_report' => 'array',
        'error_log' => 'array',
        'files_queue' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fileLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DropboxImportFileLog::class, 'dropbox_import_session_id');
    }
}
