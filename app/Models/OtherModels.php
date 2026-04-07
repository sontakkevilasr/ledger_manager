<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }
}

// ─────────────────────────────────────────────────────────────────────────────

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'module', 'action', 'display_name'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}

// ─────────────────────────────────────────────────────────────────────────────

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    protected $fillable = ['payment_type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

// ─────────────────────────────────────────────────────────────────────────────

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = ['name', 'phone', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

// ─────────────────────────────────────────────────────────────────────────────

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;   // only created_at, no updated_at

    protected $fillable = [
        'user_id', 'role_name', 'action', 'module',
        'record_id', 'record_label', 'old_values', 'new_values',
        'description', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// ─────────────────────────────────────────────────────────────────────────────

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'email_attempted', 'status', 'ip_address', 'user_agent',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
