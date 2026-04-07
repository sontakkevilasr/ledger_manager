<?php

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