<?php

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
