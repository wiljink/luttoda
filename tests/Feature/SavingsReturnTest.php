<?php

namespace Tests\Feature;

use App\Models\DailyDue;
use App\Models\Member;
use App\Models\SavingsLedger;
use App\Models\User;
use App\Services\Reports\LuttodaReportService;
use App\Services\SavingsLedgerService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class); // 35 / 7.5 / 7.5 split, return 30 Nov
        $this->staff = $this->admin();
    }

    /** Record a due through the same path the controller uses (fills the split). */
    private function due(Member $m, int $tickets, string $date): void
    {
        DailyDue::create([
            'member_id' => $m->id, 'collection_date' => $date, 'route' => $m->route,
            'ticket_quantity' => $tickets, 'collected_by' => $this->staff->id,
        ]);
        // mirror the controller's ledger deposit of the savings share
        app(SavingsLedgerService::class)->record(
            member: $m, date: $date, sourceType: 'daily_dues', txnType: 'deposit',
            amount: 35 * $tickets,
        );
    }

    public function test_entitlement_is_savings_plus_share_per_ticket(): void
    {
        $member = Member::factory()->create();
        $this->due($member, 4, '2026-06-01'); // 4 * (35 + 7.5) = 170

        $report = app(LuttodaReportService::class)->savingsReturnReport(2026);

        $this->assertCount(1, $report);
        $this->assertEqualsWithDelta(140.0, $report[0]['savings'], 0.01); // 4 * 35
        $this->assertEqualsWithDelta(30.0, $report[0]['share'], 0.01);    // 4 * 7.5
        $this->assertEqualsWithDelta(170.0, $report[0]['entitlement'], 0.01);
        $this->assertEqualsWithDelta(170.0, $report[0]['pending'], 0.01);
    }

    public function test_release_pays_cash_and_is_idempotent(): void
    {
        $member = Member::factory()->create(['savings_balance' => 0]);
        $this->due($member, 10, '2026-06-01'); // savings 350 in balance; share 75 accrued

        $this->assertEqualsWithDelta(350.0, (float) $member->fresh()->savings_balance, 0.01);

        $paid = app(LuttodaReportService::class)->markSavingsReturnReleased(2026);
        $this->assertSame(1, $paid);

        // share (75) deposited, then full entitlement (425) withdrawn -> balance = 350 + 75 - 425 = 0
        $this->assertEqualsWithDelta(0.0, (float) $member->fresh()->savings_balance, 0.01);

        $return = SavingsLedger::where('member_id', $member->id)
            ->where('source_type', 'savings_return')->first();
        $this->assertSame('withdrawal', $return->txn_type);
        $this->assertEqualsWithDelta(425.0, (float) $return->amount, 0.01);
        $this->assertSame('2026-11-30', $return->date->toDateString());

        // second run: nothing pending
        $this->assertSame(0, app(LuttodaReportService::class)->markSavingsReturnReleased(2026));
    }

    public function test_it_does_not_double_pay_an_already_released_share(): void
    {
        $member = Member::factory()->create(['savings_balance' => 0]);
        $this->due($member, 10, '2026-06-01');

        // the old rebate-pool release already put the 75 share into savings
        app(SavingsLedgerService::class)->record(
            member: $member, date: '2026-06-30', sourceType: 'rebate_release',
            txnType: 'deposit', amount: 75,
        );
        $this->assertEqualsWithDelta(425.0, (float) $member->fresh()->savings_balance, 0.01);

        app(LuttodaReportService::class)->markSavingsReturnReleased(2026);

        // no extra rebate_release; one savings_return withdrawal of 425 -> balance 0
        $this->assertSame(1, SavingsLedger::where('member_id', $member->id)->where('source_type', 'rebate_release')->count());
        $this->assertEqualsWithDelta(0.0, (float) $member->fresh()->savings_balance, 0.01);
    }

    public function test_page_and_release_route_are_gated(): void
    {
        $member = Member::factory()->create();
        $this->due($member, 5, '2026-06-01');

        $this->actingAs($this->userWithRole('collector'))
            ->get(route('reports.savings-return.page'))->assertForbidden();

        $this->actingAs($this->userWithRole('accounting'))
            ->get(route('reports.savings-return.page', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Annual Savings Return')
            ->assertSee('November 30, 2026');

        $this->actingAs($this->userWithRole('accounting'))
            ->post(route('reports.savings-return.release', 2026))
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
