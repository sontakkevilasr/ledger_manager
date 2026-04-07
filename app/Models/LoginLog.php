<?php

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
