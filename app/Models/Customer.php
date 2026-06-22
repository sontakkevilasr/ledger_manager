<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Customer extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'customer_name', 'phone', 'mobile', 'email',
        'address', 'city', 'state', 'zip_code', 'description',
        'opening_balance', 'opening_balance_type',
        'registered_on', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'opening_balance' => 'decimal:2',
        'registered_on'   => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Opening Balance as signed value ───────────────────────
    // Dr = positive  → customer owes us
    // Cr = negative  → we owe customer
    public function getOpeningBalanceSignedAttribute(): float
    {
        $amount = (float) $this->opening_balance;
        return $this->opening_balance_type === 'Cr' ? -$amount : $amount;
    }

    // ── Net Balance ───────────────────────────────────────────
    // Positive (+) → Dr → customer owes Aman Traders → "To Collect"
    // Negative (-) → Cr → Aman Traders owes customer → "To Pay"
    // Zero         → Settled
    public function getBalanceAttribute(): float
    {
        $txnNet = $this->transactions()
            ->selectRaw('SUM(debit) - SUM(credit) as net')
            ->value('net') ?? 0;

        return round((float) $txnNet + $this->opening_balance_signed, 2);
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->transactions()->sum('credit');
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->transactions()->sum('debit');
    }

    public function getBalanceLabelAttribute(): string
    {
        $bal = $this->balance;
        if ($bal > 0.01)  return 'Dr';
        if ($bal < -0.01) return 'Cr';
        return 'Nil';
    }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('customer_name', 'like', "%{$term}%")
              ->orWhere('mobile', 'like', "%{$term}%")
              ->orWhere('city', 'like', "%{$term}%");
        });
    }
}
