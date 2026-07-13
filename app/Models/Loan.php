<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'member_id', 'amount', 'interest_rate', 'total_payable', 'balance',
        'loan_date', 'due_date', 'status', 'purpose', 'approved_by',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
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

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'active']);
    }
}
