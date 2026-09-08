<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;

class LoanScheduleService
{
    /**
     * Split the loan's principal and total interest evenly across its term,
     * one installment per month starting the month after release. The final
     * installment absorbs any rounding remainder so the schedule always sums
     * exactly to total_payable.
     */
    public function generate(Loan $loan): void
    {
        if ($loan->schedules()->exists()) {
            return;
        }

        $term = max(1, (int) $loan->term_months);
        $totalInterest = round($loan->total_payable - $loan->amount, 2);

        $principalPerInstallment = round($loan->amount / $term, 2);
        $interestPerInstallment = round($totalInterest / $term, 2);

        $principalRemaining = $loan->amount;
        $interestRemaining = $totalInterest;

        for ($i = 1; $i <= $term; $i++) {
            $isLast = $i === $term;

            $principalDue = $isLast ? round($principalRemaining, 2) : $principalPerInstallment;
            $interestDue = $isLast ? round($interestRemaining, 2) : $interestPerInstallment;

            LoanSchedule::create([
                'loan_id' => $loan->id,
                'installment_no' => $i,
                'due_date' => $loan->loan_date->copy()->addMonths($i),
                'principal_due' => $principalDue,
                'interest_due' => $interestDue,
            ]);

            $principalRemaining -= $principalDue;
            $interestRemaining -= $interestDue;
        }

        $loan->update(['due_date' => $loan->loan_date->copy()->addMonths($term)]);
    }
}
