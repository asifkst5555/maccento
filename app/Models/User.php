<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'role_id',
        'status',
        'password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(ClientProject::class, 'client_project_assignments')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function projectAssignments(): HasMany
    {
        return $this->hasMany(ClientProjectAssignment::class);
    }

    public function projectComments(): HasMany
    {
        return $this->hasMany(ClientProjectComment::class);
    }

    public function roleModel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function loginRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoginRecord::class);
    }

    public function hasRole(string $role): bool
    {
        $role = strtolower(trim($role));
        if ($role === 'owner') {
            $role = 'super_admin';
        }

        $userRole = strtolower(trim((string) ($this->roleModel?->name ?: $this->role)));
        if ($userRole === 'owner') {
            $userRole = 'super_admin';
        }

        return $userRole === $role;
    }

    public function hasPermission(string $permission): bool
    {
        $permission = strtolower(trim($permission));

        $permissions = cache()->remember("user_{$this->id}_perms", 3600, function () {
            if (!$this->roleModel) {
                return [];
            }
            return $this->roleModel->permissions->pluck('name')->map(fn($p) => strtolower(trim($p)))->all();
        });

        if ($this->hasRole('super_admin')) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    public function clearPermissionCache(): void
    {
        cache()->forget("user_{$this->id}_perms");
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->role && !$user->role_id) {
                $roleName = strtolower(trim($user->role));
                if ($roleName === 'owner') {
                    $roleName = 'super_admin';
                }
                $roleModel = \App\Models\Role::where('name', $roleName)->first();
                if ($roleModel) {
                    $user->role_id = $roleModel->id;
                }
            }
        });

        static::updating(function (User $user): void {
            if ($user->isDirty('role') && !$user->isDirty('role_id')) {
                $roleName = strtolower(trim($user->role));
                if ($roleName === 'owner') {
                    $roleName = 'super_admin';
                }
                $roleModel = \App\Models\Role::where('name', $roleName)->first();
                if ($roleModel) {
                    $user->role_id = $roleModel->id;
                }
            }
        });
    }
}
