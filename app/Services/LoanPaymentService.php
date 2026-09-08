<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanPaymentAllocation;
use Illuminate\Support\Facades\DB;

class LoanPaymentService
{
    /**
     * Record a cash loan payment: assess any accrued penalties, create the
     * LoanPayment, then allocate it across the schedule. Shared by the
     * Loans screen (LoanController::addPayment) and the daily-collection
     * importer.
     *
     * A loan payment is cash collected from the member — it does NOT touch
     * the member's savings ledger / savings_balance.
     *
     * @param  array{payment_date?:string, or_number?:?string, payment_method?:?string, received_by?:?int}  $opts
     */
    public function pay(Loan $loan, float $amount, array $opts = []): LoanPayment
    {
        return DB::transaction(function () use ($loan, $amount, $opts) {
            $loan->loadMissing('schedules');
            app(LoanPenaltyService::class)->assess($loan);
            $loan->load('schedules');

            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'payment_date' => $opts['payment_date'] ?? now(),
                'amount' => $amount,
                'or_number' => $opts['or_number'] ?? null,
                'payment_method' => $opts['payment_method'] ?? 'cash',
                'received_by' => $opts['received_by'] ?? auth()->id(),
            ]);

            $this->allocate($loan, $payment);

            return $payment;
        });
    }

    /**
     * Allocate a payment across the loan's schedules, oldest due date
     * first. Within a schedule, penalty is settled before interest, and
     * interest before principal -- never subtract straight from principal,
     * or overdue penalties/interest would silently go uncollected.
     */
    public function allocate(Loan $loan, LoanPayment $payment): void
    {
        $remaining = (float) $payment->amount;

        foreach ($loan->schedules as $schedule) {
            if ($remaining <= 0) {
                break;
            }

            if ($schedule->outstanding_total <= 0) {
                continue;
            }

            $penaltyPortion = min($remaining, $schedule->outstanding_penalty);
            $remaining -= $penaltyPortion;

            $interestPortion = min($remaining, $schedule->outstanding_interest);
            $remaining -= $interestPortion;

            $principalPortion = min($remaining, $schedule->outstanding_principal);
            $remaining -= $principalPortion;

            if ($penaltyPortion <= 0 && $interestPortion <= 0 && $principalPortion <= 0) {
                continue;
            }

            LoanPaymentAllocation::create([
                'payment_id' => $payment->id,
                'schedule_id' => $schedule->id,
                'penalty_amount' => $penaltyPortion,
                'interest_amount' => $interestPortion,
                'principal_amount' => $principalPortion,
            ]);

            $schedule->increment('penalty_paid', $penaltyPortion);
            $schedule->increment('interest_paid', $interestPortion);
            $schedule->increment('principal_paid', $principalPortion);

            $openPenalty = $schedule->penalties()->whereIn('status', ['unpaid', 'partial'])->latest()->first();
            if ($openPenalty && $penaltyPortion > 0) {
                $openPenalty->increment('amount_paid', $penaltyPortion);
                $openPenalty->update([
                    'status' => $openPenalty->amount_paid >= $openPenalty->penalty_amount ? 'paid' : 'partial',
                ]);
            }

            $schedule->refresh();
            $schedule->update([
                'status' => $schedule->isFullyPaid() && $schedule->outstanding_penalty <= 0
                    ? 'paid'
                    : ($schedule->principal_paid > 0 || $schedule->interest_paid > 0 ? 'partial' : $schedule->status),
            ]);
        }

        $loan->refresh();
        $outstanding = $loan->schedules->sum(fn ($s) => $s->outstanding_total);
        $loan->balance = max(0, round($outstanding, 2));

        if ($loan->balance <= 0) {
            $loan->status = 'paid';
        } elseif ($loan->status === 'approved') {
            $loan->status = 'active';
        }

        $loan->save();
    }
}
