<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'project_code',
        'folder_name',
        'service_type',
        'property_address',
        'scheduled_at',
        'due_at',
        'status',
        'notes',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($project) {
            $project->project_code = 'PR_TEMP_' . \Illuminate\Support\Str::random(10);
            $project->folder_name = 'pr_temp_' . \Illuminate\Support\Str::random(10);
        });
        static::created(function ($project) {
            $project->project_code = 'PR' . str_pad((string) $project->id, 6, '0', STR_PAD_LEFT);
            $slugTitle = \Illuminate\Support\Str::snake($project->title ?: 'project');
            $project->folder_name = strtolower($project->project_code . '_' . $slugTitle);
            $project->saveQuietly();
        });
    }

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

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ClientServiceRequest::class);
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ClientProjectAssignment::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_project_assignments')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ClientProjectComment::class)->latest('id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'client_project_id');
    }

    public function isFullyPaid(): bool
    {
        return $this->invoices()->where('status', 'paid')->exists();
    }

    public function dropboxImportSessions(): HasMany
    {
        return $this->hasMany(DropboxImportSession::class, 'client_project_id');
    }
}
