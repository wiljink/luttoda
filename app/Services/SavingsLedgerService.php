<?php

namespace App\Services;

use App\Models\Member;
use App\Models\SavingsLedger;
use Illuminate\Support\Facades\DB;

class SavingsLedgerService
{
    /**
     * Writes one savings_ledger row AND applies the same signed amount to
     * Member::savings_balance, so the ledger's running_balance and the
     * member's cached balance can never drift apart. This should be the
     * only place in the app that touches either of them.
     */
    public function record(
        Member $member,
        string $date,
        string $sourceType,
        string $txnType,
        float $amount,
        ?\Illuminate\Database\Eloquent\Model $sourceable = null,
        ?string $remarks = null
    ): SavingsLedger {
        return DB::transaction(function () use ($member, $date, $sourceType, $txnType, $amount, $sourceable, $remarks) {
            // Lock the member row first so concurrent record() calls for the
            // same member serialize instead of racing on running_balance.
            $lockedMember = Member::whereKey($member->getKey())->lockForUpdate()->firstOrFail();

            $lastBalance = SavingsLedger::where('member_id', $lockedMember->id)
                ->orderByDesc('date')
                ->orderByDesc('ledger_id')
                ->lockForUpdate()
                ->value('running_balance') ?? 0;

            $signedAmount = $txnType === 'withdrawal' ? -abs($amount) : abs($amount);
            $newBalance = $lastBalance + $signedAmount;

            $entry = SavingsLedger::create([
                'member_id' => $lockedMember->id,
                'date' => $date,
                'source_type' => $sourceType,
                'txn_type' => $txnType,
                'amount' => $amount,
                'running_balance' => $newBalance,
                'sourceable_type' => $sourceable?->getMorphClass(),
                'sourceable_id' => $sourceable?->getKey(),
                'remarks' => $remarks,
            ]);

            $lockedMember->increment('savings_balance', $signedAmount);

            return $entry;
        });
    }
}
