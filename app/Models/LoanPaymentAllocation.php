<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id', 'schedule_id', 'penalty_amount', 'interest_amount', 'principal_amount',
    ];

    public function payment()
    {
        return $this->belongsTo(LoanPayment::class, 'payment_id');
    }

    public function schedule()
    {
        return $this->belongsTo(LoanSchedule::class, 'schedule_id');
    }
}
