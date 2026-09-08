<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    protected $fillable = [
        'loan_id', 'installment_no', 'due_date',
        'principal_due', 'interest_due', 'penalty_due',
        'principal_paid', 'interest_paid', 'penalty_paid',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function penalties()
    {
        return $this->hasMany(LoanPenalty::class, 'schedule_id');
    }

    public function allocations()
    {
        return $this->hasMany(LoanPaymentAllocation::class, 'schedule_id');
    }

    public function getOutstandingPrincipalAttribute(): float
    {
        return round($this->principal_due - $this->principal_paid, 2);
    }

    public function getOutstandingInterestAttribute(): float
    {
        return round($this->interest_due - $this->interest_paid, 2);
    }

    public function getOutstandingPenaltyAttribute(): float
    {
        return round($this->penalty_due - $this->penalty_paid, 2);
    }

    public function getOutstandingTotalAttribute(): float
    {
        return round($this->outstanding_principal + $this->outstanding_interest + $this->outstanding_penalty, 2);
    }

    public function isFullyPaid(): bool
    {
        return $this->outstanding_principal <= 0 && $this->outstanding_interest <= 0;
    }
}
