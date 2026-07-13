<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelConsumption extends Model
{
    protected $fillable = [
        'member_id', 'consumption_date', 'liters', 'amount',
        'rebate_per_liter', 'coop_deposit_per_liter', 'refill_station',
    ];

    protected $casts = [
        'consumption_date' => 'date',
    ];
    // total_rebate ug total_coop_deposit kay generated/stored column na (storedAs sa migration),
    // busa dili na sila i-fillable ug dili pud pwede i-mass assign.

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
