<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyDue extends Model
{
    protected $fillable = [
        'member_id', 'ticket_number', 'ticket_quantity', 'collection_date', 'route',
        'amount_paid', 'savings_share', 'rebate_share', 'association_share',
        'collected_by', 'remarks',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'ticket_quantity' => 'integer',
    ];

    /**
     * Per-ticket rates. Multiplied by ticket_quantity below so a
     * multi-ticket due keeps the shares (and the member's savings
     * balance) in sync with how many tickets were actually bought.
     */
    private const SAVINGS_PER_TICKET = 35.00;
    private const REBATE_PER_TICKET = 7.50;
    private const ASSOCIATION_PER_TICKET = 7.50;

    protected static function booted()
    {
        static::creating(function ($due) {
            $quantity = $due->ticket_quantity ?: 1;
            $due->ticket_quantity = $quantity;

            $due->amount_paid = $due->amount_paid
                ?? (($quantity) * (self::SAVINGS_PER_TICKET + self::REBATE_PER_TICKET + self::ASSOCIATION_PER_TICKET));

            $due->savings_share = self::SAVINGS_PER_TICKET * $quantity;
            $due->rebate_share = self::REBATE_PER_TICKET * $quantity;
            $due->association_share = self::ASSOCIATION_PER_TICKET * $quantity;
        });

        static::created(function ($due) {
            $due->member->increment('savings_balance', $due->savings_share);
        });

        static::deleted(function ($due) {
            $due->member->decrement('savings_balance', $due->savings_share);
        });
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('collection_date', $date);
    }

    /**
     * All tickets claimed for this due (supports multi-ticket purchases).
     * Prefer this over ticket() for anything that needs the full set.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Kept for backward compatibility with code/views that only expect
     * a single ticket (e.g. the manual single-ticket edit flow). Returns
     * one arbitrary ticket when several are linked — use tickets() instead
     * wherever the full range matters.
     */
    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }
}
