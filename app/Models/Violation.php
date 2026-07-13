<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    protected $fillable = [
        'member_id', 'violation_date', 'type', 'notes',
    ];

    protected $casts = [
        'violation_date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
