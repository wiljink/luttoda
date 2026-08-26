<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsLedger extends Model
{
    protected $table = 'savings_ledger'; // singular in DB, doesn't match Eloquent's default plural guess

    protected $primaryKey = 'ledger_id';

    protected $fillable = [
        'member_id', 'date', 'source_type', 'txn_type',
        'amount', 'running_balance', 'sourceable_type', 'sourceable_id', 'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function sourceable()
    {
        return $this->morphTo();
    }
}
