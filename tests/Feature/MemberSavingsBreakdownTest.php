<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Services\SavingsLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberSavingsBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_breakdown_splits_deposits_by_source_and_reconciles(): void
    {
        $member = Member::factory()->create(['savings_balance' => 0]);
        $ledger = app(SavingsLedgerService::class);

        $ledger->record($member, '2026-09-01', 'daily_dues', 'deposit', 105);
        $ledger->record($member, '2026-09-02', 'fuel_rebate', 'deposit', 60);
        $ledger->record($member, '2026-09-03', 'loan_deduction', 'withdrawal', 25);
        $ledger->record($member, '2026-09-04', 'dividend_release', 'deposit', 400);

        $breakdown = $member->fresh()->savingsBreakdown();
        $byKey = collect($breakdown['items'])->keyBy('key');

        $this->assertSame(105.0, $byKey['daily_dues']['amount']);
        $this->assertSame(60.0, $byKey['fuel_rebate']['amount']);
        $this->assertSame(-25.0, $byKey['loan_deduction']['amount']);
        $this->assertSame(400.0, $byKey['dividend_release']['amount']);

        $this->assertSame(540.0, $breakdown['total']);
        $this->assertEqualsWithDelta((float) $member->fresh()->savings_balance, $breakdown['total'], 0.01);
    }

    public function test_member_page_shows_the_breakdown(): void
    {
        $member = Member::factory()->create();
        app(SavingsLedgerService::class)->record($member, '2026-09-01', 'daily_dues', 'deposit', 70);

        $this->actingAs($this->admin())
            ->get(route('members.show', $member))
            ->assertOk()
            ->assertSee('Savings Deposit Breakdown')
            ->assertSee('From Daily Dues')
            ->assertSee('From Fuel Rebate');
    }
}
