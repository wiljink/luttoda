<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'route',
        'status',
        'daily_due_id',
        'used_on',
    ];

    protected $casts = [
        'used_on' => 'date',
    ];

    public function dailyDue()
    {
        return $this->belongsTo(DailyDue::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}
