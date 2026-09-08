<?php

namespace Tests\Feature;

use App\Models\DailyDue;
use App\Models\FuelConsumption;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenefitEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->staff = $this->admin();
    }

    private function claim(Member $member, array $overrides = [])
    {
        return $this->actingAs($this->staff)->post(route('benefits.store'), array_merge([
            'member_id' => $member->id,
            'beneficiary_type' => 'member',
            'benefit_type' => 'hospitalization',
            'claim_date' => today()->toDateString(),
            'days' => 2,
        ], $overrides));
    }

    private function giveTickets(Member $member, int $count): void
    {
        DailyDue::create([
            'member_id' => $member->id,
            'collection_date' => today(),
            'route' => $member->route,
            'ticket_quantity' => $count,
            'collected_by' => $this->staff->id,
        ]);
    }

    private function giveFuel(Member $member, float $liters): void
    {
        FuelConsumption::create([
            'member_id' => $member->id,
            'consumption_date' => today(),
            'liters' => $liters,
            'amount' => $liters * 87,
            'rebate_per_liter' => 3,
            'coop_deposit_per_liter' => 1,
        ]);
    }

    public function test_member_below_activity_threshold_is_blocked(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $this->giveTickets($member, 10);

        $this->claim($member)->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('benefits', 0);
    }

    public function test_75_tickets_makes_a_member_eligible(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $this->giveTickets($member, 75);

        $this->claim($member)->assertSessionHasNoErrors()->assertRedirect(route('benefits.index'));

        $this->assertDatabaseHas('benefits', [
            'member_id' => $member->id,
            'benefit_type' => 'hospitalization',
            'amount' => 1000, // 2 days * 500
        ]);
    }

    public function test_500_liters_diesel_makes_a_member_eligible(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $this->giveFuel($member, 500);

        $this->claim($member)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('benefits', 1);
    }

    public function test_non_member_is_never_eligible(): void
    {
        $member = Member::factory()->create(['status' => 'active', 'category' => 'non-member']);
        $this->giveTickets($member, 200);

        $this->claim($member)->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('benefits', 0);
    }

    public function test_dependent_claim_uses_the_dependent_rate(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $this->giveTickets($member, 80);
        $dependent = $member->dependents()->create(['name' => 'Child', 'relationship' => 'son', 'active' => true]);

        $this->claim($member, [
            'beneficiary_type' => 'dependent',
            'member_dependent_id' => $dependent->id,
            'days' => 3,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('benefits', [
            'member_dependent_id' => $dependent->id,
            'beneficiary_type' => 'dependent',
            'amount' => 900, // 3 days * 300
        ]);
    }

    public function test_annual_day_cap_is_enforced_across_claims(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $this->giveTickets($member, 80);

        // Cap is 10 days/year per person. First claim uses 8.
        $this->claim($member, ['days' => 8])->assertSessionHasNoErrors();

        // Second claim for 3 more days exceeds the remaining 2.
        $this->claim($member, ['days' => 3])->assertSessionHasErrors('days');

        $this->assertDatabaseCount('benefits', 1);
    }

    public function test_dependent_must_belong_to_the_member(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $this->giveTickets($member, 80);
        $otherDependent = Member::factory()->create()->dependents()->create([
            'name' => 'Stranger', 'relationship' => 'none', 'active' => true,
        ]);

        $this->claim($member, [
            'beneficiary_type' => 'dependent',
            'member_dependent_id' => $otherDependent->id,
        ])->assertSessionHasErrors('member_dependent_id');
    }
}
