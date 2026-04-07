<?php
// ═══════════════════════════════════════════════════════════
// app/Models/User.php
// ═══════════════════════════════════════════════════════════
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\LogsActivity;

class User extends Authenticatable
{
    use Notifiable, LogsActivity;

    protected $fillable = ['name', 'username', 'email', 'password', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function loginLogs()
    {
        return $this->hasMany(LoginLog::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // ── Helpers ───────────────────────────────────────────────
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->flatMap->permissions->contains('name', $permission);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function getRoleNameAttribute(): string
    {
        return $this->roles->first()?->display_name ?? 'No Role';
    }
}
