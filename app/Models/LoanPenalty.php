<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPenalty extends Model
{
    protected $fillable = [
        'loan_id', 'schedule_id', 'penalty_date', 'days_overdue',
        'base_amount', 'penalty_rate', 'penalty_amount',
        'amount_paid', 'status', 'waived_amount',
    ];

    protected $casts = [
        'penalty_date' => 'date',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function schedule()
    {
        return $this->belongsTo(LoanSchedule::class, 'schedule_id');
    }
}
