<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPenalty;
use Illuminate\Support\Carbon;

class LoanPenaltyService
{
    /**
     * Penalty basis is the total outstanding installment (principal +
     * interest still owed), prorated daily off the loan's monthly
     * penalty_rate: base * rate% / 30 * days_overdue. Each overdue schedule
     * keeps a single running loan_penalties row that grows as days pass,
     * rather than one row per day.
     */
    public function assess(Loan $loan, ?Carbon $asOf = null): void
    {
        $asOf = $asOf ?? Carbon::today();

        foreach ($loan->schedules as $schedule) {
            if ($schedule->due_date->greaterThanOrEqualTo($asOf) || $schedule->isFullyPaid()) {
                continue;
            }

            $daysOverdue = $schedule->due_date->diffInDays($asOf);
            $baseAmount = round($schedule->outstanding_principal + $schedule->outstanding_interest, 2);

            if ($baseAmount <= 0) {
                continue;
            }

            $penaltyAmount = round($baseAmount * ($loan->penalty_rate / 100) / 30 * $daysOverdue, 2);

            $penalty = LoanPenalty::firstOrNew([
                'schedule_id' => $schedule->id,
            ]);
            $penalty->loan_id = $loan->id;
            $penalty->penalty_date = $asOf;
            $penalty->days_overdue = $daysOverdue;
            $penalty->base_amount = $baseAmount;
            $penalty->penalty_rate = $loan->penalty_rate;
            $penalty->penalty_amount = $penaltyAmount;
            if (! $penalty->exists) {
                $penalty->amount_paid = 0;
                $penalty->status = 'unpaid';
                $penalty->waived_amount = 0;
            }
            $penalty->save();

            $schedule->update([
                'penalty_due' => $penaltyAmount,
                'status' => 'overdue',
            ]);
        }
    }
}
