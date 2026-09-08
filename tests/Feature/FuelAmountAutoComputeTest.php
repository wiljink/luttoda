<?php

namespace Tests\Feature;

use App\Models\FuelConsumption;
use App\Models\Member;
use App\Models\Setting;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelAmountAutoComputeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class); // diesel_price_per_liter = 87
    }

    public function test_amount_is_computed_from_liters_and_the_diesel_price_setting_when_blank(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->userWithRole('collector'))
            ->post(route('fuel.store'), [
                'member_id' => $member->id,
                'consumption_date' => '2026-09-10',
                'liters' => 40,
                // amount intentionally omitted
            ])
            ->assertRedirect(route('fuel.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fuel_consumptions', [
            'member_id' => $member->id,
            'liters' => 40,
            'amount' => 40 * 87, // 3480
        ]);
    }

    public function test_a_manually_entered_amount_is_kept(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->userWithRole('collector'))
            ->post(route('fuel.store'), [
                'member_id' => $member->id,
                'consumption_date' => '2026-09-10',
                'liters' => 40,
                'amount' => 3400, // pump gave a slightly different total
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fuel_consumptions', ['member_id' => $member->id, 'amount' => 3400]);
    }

    public function test_price_setting_change_affects_new_records(): void
    {
        Setting::set('diesel_price_per_liter', 92.50);
        $member = Member::factory()->create();

        $this->actingAs($this->userWithRole('collector'))
            ->post(route('fuel.store'), [
                'member_id' => $member->id,
                'consumption_date' => '2026-09-10',
                'liters' => 10,
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fuel_consumptions', ['member_id' => $member->id, 'amount' => 925.0]);
    }

    public function test_edit_recomputes_amount_when_left_blank(): void
    {
        $member = Member::factory()->create();
        $fuel = FuelConsumption::create([
            'member_id' => $member->id,
            'consumption_date' => '2026-09-10',
            'liters' => 20,
            'amount' => 1740,
            'rebate_per_liter' => 3,
            'coop_deposit_per_liter' => 1,
        ]);

        $this->actingAs($this->userWithRole('admin'))
            ->put(route('fuel.update', $fuel), [
                'liters' => 30,
                // amount omitted -> recompute 30 * 87
            ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(30 * 87, $fuel->fresh()->amount, 0.01);
    }
}
