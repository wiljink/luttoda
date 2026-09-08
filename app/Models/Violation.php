<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    protected $fillable = [
        'member_id', 'violation_date', 'type', 'notes',
        'sanction', 'sanction_until', 'sanction_lifted_at',
    ];

    protected $casts = [
        'violation_date' => 'date',
        'sanction_until' => 'date',
        'sanction_lifted_at' => 'datetime',
    ];

    public const SANCTIONS = ['suspension', 'termination', 'dismembership'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function hasSanction(): bool
    {
        return $this->sanction !== null && $this->sanction_lifted_at === null;
    }
}
