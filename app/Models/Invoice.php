<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Invoice extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'issue_date'     => 'date',
        'due_date'       => 'date',
        'subtotal'       => 'decimal:2',
        'discount_amount'=> 'decimal:2',
        'tax_amount'     => 'decimal:2',
        'total_amount'   => 'decimal:2',
        'amount_paid'    => 'decimal:2',
        'balance_due'    => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Recalculate amount_paid and balance_due from payments.
     */
    public function recalculateBalance(): void
    {
        $paid = $this->payments()->sum('amount');
        $this->amount_paid = $paid;
        $this->balance_due = $this->total_amount - $paid;
        $this->status = match (true) {
            $this->balance_due <= 0 => 'Paid',
            $paid > 0               => 'Partial',
            default                 => 'Issued',
        };
        $this->save();
    }
}
