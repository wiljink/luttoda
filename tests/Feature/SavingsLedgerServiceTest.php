<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Services\SavingsLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Runs against the full migration set. The two source_type enum-widening
 * migrations are MySQL-only (guarded by a driver check); a later migration
 * converts source_type to a plain string for every driver, so SQLite gets
 * a working table and open-ended source_type values.
 */
class SavingsLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_writes_ledger_entry_with_correct_member_id(): void
    {
        $member = Member::factory()->create(['savings_balance' => 0]);

        $entry = app(SavingsLedgerService::class)->record(
            member: $member,
            date: '2026-08-26',
            sourceType: 'daily_dues',
            txnType: 'deposit',
            amount: 35.00,
        );

        $this->assertSame($member->id, $entry->member_id);
        $this->assertEquals(35.00, $entry->running_balance);
    }

    public function test_record_keeps_member_savings_balance_in_sync_with_ledger(): void
    {
        $member = Member::factory()->create(['savings_balance' => 0]);
        $service = app(SavingsLedgerService::class);

        $service->record($member, '2026-08-01', 'daily_dues', 'deposit', 35.00);
        $service->record($member, '2026-08-02', 'fuel_rebate', 'deposit', 2.00);
        $service->record($member, '2026-08-03', 'loan_deduction', 'withdrawal', 10.00);

        $member->refresh();

        $this->assertEquals(27.00, $member->savings_balance);
        $this->assertEquals(27.00, $member->savingsLedgers()->latest('ledger_id')->value('running_balance'));
    }

    public function test_record_accepts_benefit_release_source_type(): void
    {
        $member = Member::factory()->create(['savings_balance' => 0]);

        $entry = app(SavingsLedgerService::class)->record(
            member: $member,
            date: '2026-08-26',
            sourceType: 'benefit_release',
            txnType: 'deposit',
            amount: 700.00,
        );

        $this->assertSame('benefit_release', $entry->source_type);
        $this->assertEquals(700.00, $member->fresh()->savings_balance);
    }
}
