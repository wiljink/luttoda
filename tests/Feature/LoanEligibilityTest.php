<?php

namespace Tests\Feature;

use App\Models\DailyDue;
use App\Models\FuelConsumption;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class); // 75 tickets / 500 L
        $this->staff = $this->admin();
    }

    private function apply(Member $member, array $overrides = [])
    {
        return $this->actingAs($this->staff)->post(route('loans.store'), array_merge([
            'member_id' => $member->id,
            'type' => 'cash',
            'amount' => 5000,
            'term_months' => 6,
            'interest_rate' => 2,
        ], $overrides));
    }

    private function giveTickets(Member $m, int $n): void
    {
        DailyDue::create([
            'member_id' => $m->id, 'collection_date' => today(), 'route' => $m->route,
            'ticket_quantity' => $n, 'collected_by' => $this->staff->id,
        ]);
    }

    private function giveFuel(Member $m, float $liters): void
    {
        FuelConsumption::create([
            'member_id' => $m->id, 'consumption_date' => today(), 'liters' => $liters,
            'amount' => $liters * 87, 'rebate_per_liter' => 3, 'coop_deposit_per_liter' => 1,
        ]);
    }

    public function test_member_below_threshold_cannot_apply(): void
    {
        $member = Member::factory()->create();
        $this->giveTickets($member, 20);

        $this->apply($member)->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_75_tickets_unlocks_a_loan_application(): void
    {
        $member = Member::factory()->create();
        $this->giveTickets($member, 75);

        $this->apply($member)->assertSessionHasNoErrors()->assertRedirect(route('loans.index'));
        $this->assertDatabaseHas('loans', ['member_id' => $member->id, 'status' => 'pending']);
    }

    public function test_500_liters_diesel_unlocks_a_loan_application(): void
    {
        $member = Member::factory()->create();
        $this->giveFuel($member, 520);

        $this->apply($member)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('loans', 1);
    }

    public function test_terminated_member_cannot_apply_even_with_activity(): void
    {
        $member = Member::factory()->create(['status' => 'terminated']);
        $this->giveTickets($member, 200);

        $this->apply($member)->assertSessionHasErrors('member_id');
    }

    public function test_approval_rechecks_the_threshold(): void
    {
        $member = Member::factory()->create(['savings_balance' => 1000]);
        $this->giveTickets($member, 80);

        // Application goes through.
        $this->apply($member)->assertSessionHasNoErrors();
        $loan = Loan::first();

        // Activity is corrected away (dues deleted) before approval.
        DailyDue::where('member_id', $member->id)->delete();

        $this->actingAs($this->staff)
            ->post(route('loans.approve', $loan))
            ->assertSessionHasErrors('loan');

        $this->assertSame('pending', $loan->fresh()->status);
    }

    public function test_create_form_shows_the_requirement(): void
    {
        Member::factory()->create();

        $this->actingAs($this->staff)
            ->get(route('loans.create'))
            ->assertOk()
            ->assertSee('75 tickets')
            ->assertSee('Diesel Loan');
    }
}
