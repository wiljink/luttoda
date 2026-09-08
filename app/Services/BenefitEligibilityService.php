<?php

namespace App\Services;

use App\Models\Benefit;
use App\Models\DailyDue;
use App\Models\FuelConsumption;
use App\Models\Member;
use App\Models\MemberDependent;
use App\Models\Setting;

/**
 * Encapsulates the LUTTODA benefit rules, and the shared yearly activity
 * gate (meetsActivityThreshold) that also gates loan applications.
 *
 *  - Only members with category 'member' (not 'non-member') and a
 *    non-terminated standing may claim a benefit, for themselves or a
 *    dependent. (Loans: any non-terminated member who meets the gate.)
 *  - The member must have reached an activity threshold for the year:
 *    tickets >= eligibility_tickets_a  OR
 *    diesel liters >= eligibility_diesel_liters  OR
 *    tickets >= eligibility_tickets_b
 *  - Hospitalization is paid per day (benefit_primary_daily for the
 *    member, benefit_dependent_daily for a dependent), capped at
 *    benefit_max_days_per_year days per beneficiary per calendar year.
 */
class BenefitEligibilityService
{
    public function ticketsYtd(Member $member, int $year): int
    {
        return (int) DailyDue::where('member_id', $member->id)
            ->whereYear('collection_date', $year)
            ->sum('ticket_quantity');
    }

    public function litersYtd(Member $member, int $year): float
    {
        return (float) FuelConsumption::where('member_id', $member->id)
            ->whereYear('consumption_date', $year)
            ->sum('liters');
    }

    /**
     * The shared yearly activity gate used for BOTH benefit claims and
     * loan applications: the member must have reached
     *   eligibility_tickets_a tickets  OR
     *   eligibility_diesel_liters liters of diesel  OR
     *   eligibility_tickets_b tickets
     * within the given calendar year.
     */
    public function meetsActivityThreshold(Member $member, int $year): bool
    {
        $tickets = $this->ticketsYtd($member, $year);
        $liters = $this->litersYtd($member, $year);

        return $tickets >= (int) Setting::get('eligibility_tickets_a', 75)
            || $liters >= (float) Setting::get('eligibility_diesel_liters', 500)
            || $tickets >= (int) Setting::get('eligibility_tickets_b', 150);
    }

    /** Human "not enough activity yet" line for form errors. */
    public function activityShortfall(Member $member, int $year): string
    {
        $a = (int) Setting::get('eligibility_tickets_a', 75);
        $l = (float) Setting::get('eligibility_diesel_liters', 500);

        return "Member has not met the {$year} activity requirement — "
            ."{$this->ticketsYtd($member, $year)}/{$a} tickets or "
            .rtrim(rtrim(number_format($this->litersYtd($member, $year), 2), '0'), '.')
            ."/{$l} L diesel.";
    }

    public function isEligible(Member $member, int $year): bool
    {
        if (! $member->isMember() || $member->isTerminated()) {
            return false;
        }

        return $this->meetsActivityThreshold($member, $year);
    }

    /** Human explanation of why a member is not eligible (or '' if they are). */
    public function ineligibilityReason(Member $member, int $year): string
    {
        if (! $member->isMember()) {
            return 'Non-members cannot file benefit claims.';
        }
        if ($member->isTerminated()) {
            return 'Terminated members cannot file benefit claims.';
        }
        if ($this->isEligible($member, $year)) {
            return '';
        }

        $a = (int) Setting::get('eligibility_tickets_a', 75);
        $l = (float) Setting::get('eligibility_diesel_liters', 500);

        return "Member has not reached the {$year} activity threshold "
            ."({$this->ticketsYtd($member, $year)}/{$a} tickets, "
            .rtrim(rtrim(number_format($this->litersYtd($member, $year), 2), '0'), '.')
            ."/{$l} L diesel).";
    }

    public function maxDaysPerYear(): int
    {
        return (int) Setting::get('benefit_max_days_per_year', 15);
    }

    /**
     * Hospitalization days already claimed this year for a beneficiary
     * (rejected claims don't count).
     *
     * @param  Member|MemberDependent  $beneficiary
     */
    public function daysUsed($beneficiary, int $year): int
    {
        $query = Benefit::where('benefit_type', 'hospitalization')
            ->where('status', '!=', 'rejected')
            ->whereYear('claim_date', $year);

        if ($beneficiary instanceof MemberDependent) {
            $query->where('member_dependent_id', $beneficiary->id);
        } else {
            $query->where('member_id', $beneficiary->id)
                ->where('beneficiary_type', 'member');
        }

        return (int) $query->sum('days');
    }

    /** @param  Member|MemberDependent  $beneficiary */
    public function remainingDays($beneficiary, int $year): int
    {
        return max(0, $this->maxDaysPerYear() - $this->daysUsed($beneficiary, $year));
    }

    /** Compact summary for member views. */
    public function summary(Member $member, int $year): array
    {
        return [
            'year' => $year,
            'tickets_ytd' => $this->ticketsYtd($member, $year),
            'liters_ytd' => $this->litersYtd($member, $year),
            'eligible' => $this->isEligible($member, $year),
            'reason' => $this->ineligibilityReason($member, $year),
            'remaining_days' => $this->remainingDays($member, $year),
            'max_days' => $this->maxDaysPerYear(),
        ];
    }
}
