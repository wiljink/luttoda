<?php

namespace Tests\Feature;

use App\Models\DailyDue;
use App\Models\FuelConsumption;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Services\DieselLoanService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DieselLoanTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class); // factor 2, percentage 0.80; gate 75 tix / 500 L
        $this->staff = $this->admin();
    }

    private function fuel(Member $m, float $liters): void
    {
        FuelConsumption::create([
            'member_id' => $m->id, 'consumption_date' => today(), 'liters' => $liters,
            'amount' => $liters * 87, 'rebate_per_liter' => 3, 'coop_deposit_per_liter' => 1,
        ]);
    }

    /** Meets the yearly activity gate without adding diesel litres. */
    private function passGate(Member $m): void
    {
        DailyDue::create([
            'member_id' => $m->id, 'collection_date' => today(), 'route' => $m->route,
            'ticket_quantity' => 80, 'collected_by' => $this->staff->id,
        ]);
    }

    private function applyDiesel(Member $m)
    {
        return $this->actingAs($this->staff)->post(route('loans.store'), [
            'member_id' => $m->id,
            'type' => 'diesel',
            'term_months' => 6,
            'interest_rate' => 2,
        ]);
    }

    public function test_diesel_loan_amount_is_liters_times_factor_times_percentage(): void
    {
        $member = Member::factory()->create();
        $this->passGate($member);
        $this->fuel($member, 100); // 100 * 2 * 0.80 = 160

        $this->applyDiesel($member)->assertSessionHasNoErrors()->assertRedirect(route('loans.index'));

        $loan = Loan::first();
        $this->assertSame('diesel', $loan->type);
        $this->assertEqualsWithDelta(160.0, (float) $loan->amount, 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $loan->liters_basis, 0.01);
    }

    public function test_a_second_diesel_loan_only_uses_the_un_borrowed_liters(): void
    {
        $member = Member::factory()->create();
        $this->passGate($member);
        $this->fuel($member, 100);
        $this->applyDiesel($member); // borrows 100 L

        $this->fuel($member, 40); // 40 L more purchased
        $this->applyDiesel($member)->assertSessionHasNoErrors();

        $second = Loan::orderByDesc('id')->first();
        $this->assertEqualsWithDelta(40.0, (float) $second->liters_basis, 0.01);
        $this->assertEqualsWithDelta(64.0, (float) $second->amount, 0.01); // 40 * 1.6
    }

    public function test_no_un_borrowed_liters_blocks_the_diesel_loan(): void
    {
        $member = Member::factory()->create();
        $this->passGate($member);
        $this->fuel($member, 50);
        $this->applyDiesel($member); // uses all 50 L

        $this->applyDiesel($member)->assertSessionHasErrors('member_id');
        $this->assertSame(1, Loan::count());
    }

    public function test_diesel_loan_still_needs_the_activity_gate(): void
    {
        // Only 30 L diesel, no tickets -> below the 75 tix / 500 L gate.
        $member = Member::factory()->create();
        $this->fuel($member, 30);

        $this->applyDiesel($member)->assertSessionHasErrors('member_id');
        $this->assertSame(0, Loan::count());

        // Reaching the gate (500 L diesel) then unlocks it.
        $this->fuel($member, 500);
        $this->applyDiesel($member)->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(530 * 1.6, (float) Loan::first()->amount, 0.01);
    }

    public function test_diesel_loan_is_approved_and_scheduled_like_a_cash_loan(): void
    {
        $member = Member::factory()->create(['savings_balance' => 1000]);
        $this->passGate($member);
        $this->fuel($member, 200);
        $this->applyDiesel($member);
        $loan = Loan::first();

        $this->actingAs($this->staff)->post(route('loans.approve', $loan))->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertSame('approved', $loan->status);
        $this->assertSame(6, $loan->schedules()->count());
    }

    public function test_terminated_member_cannot_take_a_diesel_loan(): void
    {
        $member = Member::factory()->create(['status' => 'terminated']);
        $this->passGate($member);
        $this->fuel($member, 100);

        $this->applyDiesel($member)->assertSessionHasErrors('member_id');
    }

    public function test_service_available_liters_helper(): void
    {
        $member = Member::factory()->create();
        $this->fuel($member, 120);

        $svc = app(DieselLoanService::class);
        $this->assertEqualsWithDelta(120.0, $svc->availableLiters($member, (int) now()->year), 0.01);
        $this->assertEqualsWithDelta(192.0, $svc->maxAmount($member, (int) now()->year), 0.01);
    }
}
