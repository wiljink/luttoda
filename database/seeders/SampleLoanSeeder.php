<?php

namespace Database\Seeders;

use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Services\LoanScheduleService;
use Illuminate\Database\Seeder;

/**
 * Demo helper for the "September 8-21" sample import set — NOT wired into
 * DatabaseSeeder. Run explicitly:
 *
 *     php artisan db:seed --class=Database\\Seeders\\SampleLoanSeeder
 *
 * It grants + approves one cash loan for Teresita Dela Cruz so the
 * loan-payment rows on her daily-collection sheet import successfully.
 * Marilou Aquino is left without a loan on purpose, so her loan-payment
 * row is skipped with the "no approved loan" message — demonstrating the
 * gate.
 */
class SampleLoanSeeder extends Seeder
{
    public function run(): void
    {
        $member = Member::where('plate_number', 'ABC-2345')->first()
            ?? Member::where('lastname', 'Dela Cruz')->first();

        if (! $member) {
            $this->command?->warn('SampleLoanSeeder: Teresita Dela Cruz not found — import the members template first.');

            return;
        }

        if ($member->loans()->whereIn('status', ['approved', 'active'])->exists()) {
            $this->command?->info('SampleLoanSeeder: Teresita Dela Cruz already has an active loan — nothing to do.');

            return;
        }

        $admin = User::query()->first();

        $loan = Loan::create([
            'member_id' => $member->id,
            'type' => 'cash',
            'amount' => 5000,
            'interest_rate' => 3,
            'term_months' => 6,
            'penalty_rate' => 2,
            'loan_date' => '2026-09-01',
            'status' => 'approved',
            'purpose' => 'Sample loan for the September 8-21 import demo',
            'approved_by' => $admin?->id,
        ]);

        app(LoanScheduleService::class)->generate($loan->fresh());

        $this->command?->info("SampleLoanSeeder: approved ₱5,000 cash loan #{$loan->id} for {$member->full_name}.");
    }
}
