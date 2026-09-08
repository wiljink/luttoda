<?php

namespace App\Services;

use App\Models\FuelConsumption;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Setting;

/**
 * Diesel loan: a cash loan drawn against the diesel a member purchased
 * (the same diesel purchases that earn them tickets). It is repaid on the
 * same amortised schedule as a normal loan.
 *
 *   amount = un-borrowed litres for the year
 *          × diesel_loan_liter_factor (2)
 *          × diesel_loan_percentage (0.80)
 *
 * "Un-borrowed" = the member's diesel litres for the year, minus the
 * litres that already backed a diesel loan this year (any status except
 * rejected).
 */
class DieselLoanService
{
    public function literFactor(): float
    {
        return (float) Setting::get('diesel_loan_liter_factor', 2);
    }

    public function percentage(): float
    {
        return (float) Setting::get('diesel_loan_percentage', 0.80);
    }

    public function purchasedLiters(Member $member, int $year): float
    {
        return (float) FuelConsumption::where('member_id', $member->id)
            ->whereYear('consumption_date', $year)
            ->sum('liters');
    }

    public function borrowedLiters(Member $member, int $year): float
    {
        return (float) Loan::where('member_id', $member->id)
            ->diesel()
            ->where('status', '!=', 'rejected')
            ->whereYear('loan_date', $year)
            ->sum('liters_basis');
    }

    public function availableLiters(Member $member, int $year): float
    {
        return max(0, round($this->purchasedLiters($member, $year) - $this->borrowedLiters($member, $year), 2));
    }

    /** Loan amount for all of the member's currently un-borrowed litres. */
    public function maxAmount(Member $member, int $year): float
    {
        return round($this->availableLiters($member, $year) * $this->literFactor() * $this->percentage(), 2);
    }

    public function amountForLiters(float $liters): float
    {
        return round($liters * $this->literFactor() * $this->percentage(), 2);
    }
}
