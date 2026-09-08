<?php

namespace Tests\Feature;

use App\Models\Setting;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_populates_defaults(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->assertSame(87.0, Setting::get('diesel_price_per_liter'));
        $this->assertSame(35.0, Setting::get('dues_savings_per_ticket'));
        $this->assertSame(10, Setting::get('benefit_max_days_per_year'));
    }

    public function test_get_casts_by_type_and_returns_default_when_missing(): void
    {
        Setting::create(['key' => 'x_int', 'value' => '9', 'type' => 'int', 'group' => 'general']);
        Setting::create(['key' => 'x_float', 'value' => '1.5', 'type' => 'float', 'group' => 'general']);
        Setting::create(['key' => 'x_bool', 'value' => '1', 'type' => 'bool', 'group' => 'general']);

        $this->assertSame(9, Setting::get('x_int'));
        $this->assertSame(1.5, Setting::get('x_float'));
        $this->assertTrue(Setting::get('x_bool'));
        $this->assertSame('fallback', Setting::get('missing_key', 'fallback'));
    }

    public function test_set_persists_and_busts_cache(): void
    {
        Setting::create(['key' => 'diesel_price_per_liter', 'value' => '87', 'type' => 'float', 'group' => 'fuel']);

        $this->assertSame(87.0, Setting::get('diesel_price_per_liter'));

        Setting::set('diesel_price_per_liter', 90);

        $this->assertSame(90.0, Setting::get('diesel_price_per_liter'));
        $this->assertDatabaseHas('settings', ['key' => 'diesel_price_per_liter', 'value' => '90']);
    }

    public function test_settings_page_is_admin_only(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->get(route('settings.index'))->assertRedirect();

        $collector = $this->userWithRole('collector');
        $this->actingAs($collector)->get(route('settings.index'))->assertForbidden();

        $this->actingAs($this->admin())->get(route('settings.index'))->assertOk();
    }

    public function test_admin_can_update_settings(): void
    {
        $this->seed(SettingsSeeder::class);

        $payload = ['settings' => ['diesel_price_per_liter' => '92.50', 'benefit_max_days_per_year' => '20']];

        $this->actingAs($this->admin())
            ->put(route('settings.update'), $payload)
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success');

        $this->assertSame(92.5, Setting::get('diesel_price_per_liter'));
        $this->assertSame(20, Setting::get('benefit_max_days_per_year'));
    }
}
