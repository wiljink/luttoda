<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Violation;
use Illuminate\Support\Carbon;

/**
 * Keeps Member::status in sync with the sanctions recorded on their
 * violations. A violation's `sanction` column drives the member's
 * standing:
 *
 *   suspension                -> status 'suspended', suspended_until set
 *   termination|dismembership -> status 'terminated'
 *   (none / null)             -> status untouched
 */
class MemberSanctionService
{
    /**
     * Apply the sanction on a freshly saved/updated violation to its
     * member. Safe to call for violations with no sanction (no-op).
     */
    public function apply(Violation $violation): void
    {
        $member = $violation->member;

        if (! $member) {
            return;
        }

        match ($violation->sanction) {
            'suspension' => $member->forceFill([
                'status' => 'suspended',
                'suspended_until' => $violation->sanction_until,
            ])->save(),
            'termination', 'dismembership' => $member->forceFill([
                'status' => 'terminated',
                'suspended_until' => null,
            ])->save(),
            default => null,
        };
    }

    /**
     * Restore any member whose suspension has run its course to 'active'
     * and stamp the originating violation. Returns the number reactivated.
     */
    public function liftExpired(?Carbon $asOf = null): int
    {
        $asOf = $asOf ?? Carbon::today();

        $members = Member::query()
            ->where('status', 'suspended')
            ->whereNotNull('suspended_until')
            ->whereDate('suspended_until', '<=', $asOf)
            ->get();

        foreach ($members as $member) {
            $member->forceFill([
                'status' => 'active',
                'suspended_until' => null,
            ])->save();

            $member->violations()
                ->where('sanction', 'suspension')
                ->whereNull('sanction_lifted_at')
                ->update(['sanction_lifted_at' => now()]);
        }

        return $members->count();
    }
}
