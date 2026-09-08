<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'member_id', 'type', 'amount', 'liters_basis', 'interest_rate', 'term_months', 'penalty_rate',
        'total_payable', 'balance', 'loan_date', 'due_date', 'status', 'purpose', 'approved_by',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'liters_basis' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($loan) {
            $interest = $loan->amount * ($loan->interest_rate / 100);
            $loan->total_payable = $loan->amount + $interest;
            $loan->balance = $loan->total_payable;
        });
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function schedules()
    {
        return $this->hasMany(LoanSchedule::class)->orderBy('installment_no');
    }

    public function penalties()
    {
        return $this->hasMany(LoanPenalty::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'active']);
    }

    public function scopeDiesel($query)
    {
        return $query->where('type', 'diesel');
    }

    public function isDiesel(): bool
    {
        return $this->type === 'diesel';
    }
}
