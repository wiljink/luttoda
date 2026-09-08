<?php

namespace App\Services;

use App\Models\AlkansiyaContribution;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

/**
 * Single write path for the voluntary Alkansiya (SSS) savings pot.
 *
 * Mirrors SavingsLedgerService: every write both inserts an
 * alkansiya_contributions row AND applies the same signed amount to
 * Member::alkansiya_balance, so the two can never drift apart. This is
 * the only place in the app that touches alkansiya_balance.
 *
 * Alkansiya is deliberately kept out of the regular savings ledger and
 * savings_balance — it is a separate, member-owned fund.
 */
class AlkansiyaService
{
    /**
     * @param  array{daily_due_id?:int|null, collected_by?:int|null, remarks?:string|null}  $opts
     */
    public function record(Member $member, string $date, float $amount, array $opts = []): AlkansiyaContribution
    {
        return DB::transaction(function () use ($member, $date, $amount, $opts) {
            $lockedMember = Member::whereKey($member->getKey())->lockForUpdate()->firstOrFail();

            $contribution = AlkansiyaContribution::create([
                'member_id' => $lockedMember->id,
                'daily_due_id' => $opts['daily_due_id'] ?? null,
                'contribution_date' => $date,
                'amount' => abs($amount),
                'collected_by' => $opts['collected_by'] ?? null,
                'remarks' => $opts['remarks'] ?? null,
            ]);

            $lockedMember->increment('alkansiya_balance', abs($amount));

            return $contribution;
        });
    }

    /** Back out a contribution: subtract it from the balance and delete the row. */
    public function reverse(AlkansiyaContribution $contribution): void
    {
        DB::transaction(function () use ($contribution) {
            $lockedMember = Member::whereKey($contribution->member_id)->lockForUpdate()->firstOrFail();
            $lockedMember->decrement('alkansiya_balance', abs((float) $contribution->amount));
            $contribution->delete();
        });
    }
}
