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
     * Per-ticket rate fallbacks. The live values come from Settings
     * (keys dues_savings_per_ticket / dues_rebate_per_ticket /
     * dues_association_per_ticket); these consts are used only when a
     * setting row is missing. Multiplied by ticket_quantity below so a
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

            $savingsPerTicket = (float) Setting::get('dues_savings_per_ticket', self::SAVINGS_PER_TICKET);
            $rebatePerTicket = (float) Setting::get('dues_rebate_per_ticket', self::REBATE_PER_TICKET);
            $associationPerTicket = (float) Setting::get('dues_association_per_ticket', self::ASSOCIATION_PER_TICKET);

            $due->amount_paid = $due->amount_paid
                ?? ($quantity * ($savingsPerTicket + $rebatePerTicket + $associationPerTicket));

            $due->savings_share = $savingsPerTicket * $quantity;
            $due->rebate_share = $rebatePerTicket * $quantity;
            $due->association_share = $associationPerTicket * $quantity;
        });

        // Balance updates are NOT handled here. DailyDuesController::store()
        // writes the savings_share to savings_ledger via SavingsLedgerService,
        // which is the single place that adjusts Member::savings_balance --
        // keeping this model event too would double-count every deposit.
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
