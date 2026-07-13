<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Benefit extends Model
{
    protected $fillable = [
        'member_id', 'benefit_type', 'amount', 'claim_date', 'days',
        'status', 'remarks', 'processed_by', 'processed_date',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'processed_date' => 'date',
    ];

    // Default nga amount base sa benefit_type, gamiton sa controller
    public const RATES = [
        'hospitalization' => 500.00, // per day
        'burial' => 3000.00,         // flat
        'sss' => 700.00,             // savings
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
