<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlkansiyaContribution extends Model
{
    protected $fillable = [
        'member_id', 'daily_due_id', 'contribution_date', 'amount', 'collected_by', 'remarks',
    ];

    protected $casts = [
        'contribution_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function dailyDue()
    {
        return $this->belongsTo(DailyDue::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
