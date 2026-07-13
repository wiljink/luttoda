<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeExpense extends Model
{
    protected $fillable = [
        'transaction_date', 'type', 'category', 'description',
        'amount', 'recorded_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }
}
