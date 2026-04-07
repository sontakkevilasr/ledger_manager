<?php

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
