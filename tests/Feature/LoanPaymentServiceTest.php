<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Member;
use App\Services\LoanPaymentService;
use App\Services\LoanScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function approvedLoan(Member $member, float $amount = 5000): Loan
    {
        $loan = Loan::create([
            'member_id' => $member->id,
            'type' => 'cash',
            'amount' => $amount,
            'interest_rate' => 0,
            'term_months' => 5,
            'penalty_rate' => 2,
            'loan_date' => now()->toDateString(),
            'status' => 'approved',
        ]);

        app(LoanScheduleService::class)->generate($loan->fresh());

        return $loan->fresh();
    }

    public function test_pay_reduces_the_loan_balance_without_touching_savings(): void
    {
        $member = Member::factory()->create(['savings_balance' => 1000]);
        $loan = $this->approvedLoan($member, 5000);
        $receiver = $this->admin();

        app(LoanPaymentService::class)->pay($loan, 1500, ['payment_method' => 'cash', 'received_by' => $receiver->id]);

        $loan->refresh();
        $this->assertEqualsWithDelta(3500.0, (float) $loan->balance, 0.01);
        $this->assertEqualsWithDelta(1000.0, (float) $member->fresh()->savings_balance, 0.01);
        $this->assertDatabaseCount('savings_ledger', 0);
        $this->assertDatabaseHas('loan_payments', ['loan_id' => $loan->id, 'amount' => 1500, 'payment_method' => 'cash']);
    }

    public function test_addpayment_route_does_not_write_a_savings_deduction(): void
    {
        $member = Member::factory()->create(['savings_balance' => 1000]);
        $loan = $this->approvedLoan($member, 5000);

        $this->actingAs($this->admin())
            ->post(route('loans.payment', $loan), ['amount' => 1000])
            ->assertRedirect();

        $this->assertEqualsWithDelta(1000.0, (float) $member->fresh()->savings_balance, 0.01);
        $this->assertDatabaseMissing('savings_ledger', ['source_type' => 'loan_deduction']);
    }
}
