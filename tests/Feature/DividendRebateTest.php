<?php

namespace Tests\Feature;

use App\Models\FuelConsumption;
use App\Models\Member;
use App\Models\SavingsLedger;
use App\Models\Setting;
use App\Services\Reports\LuttodaReportService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DividendRebateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class); // diesel_price_per_liter = 87
    }

    private function fuel(Member $member, float $liters, ?string $date = null): void
    {
        FuelConsumption::create([
            'member_id' => $member->id,
            'consumption_date' => $date ?? '2026-03-01',
            'liters' => $liters,
            'amount' => $liters * 87,
            'rebate_per_liter' => 3,
            'coop_deposit_per_liter' => 1,
        ]);
    }

    public function test_dividend_is_half_of_liters_times_diesel_price(): void
    {
        $member = Member::factory()->create();
        $this->fuel($member, 42);

        $report = app(LuttodaReportService::class)->dividendRebateReport(2026);

        $this->assertCount(1, $report);
        $this->assertEqualsWithDelta(42 * 87 / 2, $report[0]['dividend'], 0.01);
        $this->assertEqualsWithDelta(1827.0, $report[0]['pending'], 0.01);
    }

    public function test_non_members_still_receive_the_dividend(): void
    {
        $nonMember = Member::factory()->create(['category' => 'non-member']);
        $this->fuel($nonMember, 100);

        $report = app(LuttodaReportService::class)->dividendRebateReport(2026);

        $this->assertCount(1, $report);
        $this->assertSame($nonMember->id, $report[0]['member_id']);
        $this->assertEqualsWithDelta(100 * 87 / 2, $report[0]['dividend'], 0.01);
    }

    public function test_terminated_members_are_excluded(): void
    {
        $terminated = Member::factory()->create(['status' => 'terminated']);
        $this->fuel($terminated, 100);

        $this->assertSame([], app(LuttodaReportService::class)->dividendRebateReport(2026));
    }

    public function test_release_credits_savings_via_the_ledger_and_is_idempotent(): void
    {
        $member = Member::factory()->create(['savings_balance' => 0]);
        $this->fuel($member, 100); // dividend = 100*87/2 = 4350

        $service = app(LuttodaReportService::class);

        $credited = $service->markDividendReleased(2026);
        $this->assertSame(1, $credited);

        $member->refresh();
        $this->assertEqualsWithDelta(4350.0, $member->savings_balance, 0.01);

        $entry = SavingsLedger::where('member_id', $member->id)->latest('ledger_id')->first();
        $this->assertSame('dividend_release', $entry->source_type);
        $this->assertEqualsWithDelta(4350.0, $entry->running_balance, 0.01);

        // Second run: nothing pending.
        $this->assertSame(0, $service->markDividendReleased(2026));
        $this->assertEqualsWithDelta(4350.0, $member->fresh()->savings_balance, 0.01);
    }

    public function test_diesel_price_setting_drives_the_dividend(): void
    {
        Setting::set('diesel_price_per_liter', 90);

        $member = Member::factory()->create();
        $this->fuel($member, 10);

        $report = app(LuttodaReportService::class)->dividendRebateReport(2026);
        $this->assertEqualsWithDelta(450.0, $report[0]['dividend'], 0.01); // 10 * 90 / 2
    }

    public function test_release_route_is_gated_and_flashes(): void
    {
        $member = Member::factory()->create();
        $this->fuel($member, 20);

        $this->actingAs($this->userWithRole('collector'))
            ->post(route('reports.dividend-rebate.release', 2026))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('accounting'))
            ->post(route('reports.dividend-rebate.release', 2026))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_page_renders(): void
    {
        $member = Member::factory()->create();
        $this->fuel($member, 30);

        $this->actingAs($this->admin())
            ->get(route('reports.dividend-rebate.page', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Annual Dividend Rebate')
            ->assertSee($member->full_name);
    }
}
