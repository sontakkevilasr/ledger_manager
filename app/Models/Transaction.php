<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Transaction extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'customer_id', 'payment_type_id', 'agent_id',
        'description', 'transaction_date',
        'credit', 'debit', 'type', 'remark',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'credit'           => 'decimal:2',
        'debit'            => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────────────────────
    public function scopeCredits($query)
    {
        return $query->where('type', 'Credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('type', 'Debit');
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
