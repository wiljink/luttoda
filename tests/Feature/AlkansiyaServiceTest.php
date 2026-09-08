<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Services\AlkansiyaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlkansiyaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_increments_the_separate_alkansiya_balance_only(): void
    {
        $member = Member::factory()->create(['savings_balance' => 500, 'alkansiya_balance' => 0]);
        $service = app(AlkansiyaService::class);

        $service->record($member, '2026-09-08', 50);
        $service->record($member, '2026-09-15', 30);

        $member->refresh();
        $this->assertEqualsWithDelta(80.0, (float) $member->alkansiya_balance, 0.01);
        $this->assertEqualsWithDelta(500.0, (float) $member->savings_balance, 0.01);
        $this->assertDatabaseCount('alkansiya_contributions', 2);
    }

    public function test_reverse_backs_a_contribution_out(): void
    {
        $member = Member::factory()->create(['alkansiya_balance' => 0]);
        $service = app(AlkansiyaService::class);

        $c = $service->record($member, '2026-09-08', 50);
        $service->reverse($c);

        $this->assertEqualsWithDelta(0.0, (float) $member->fresh()->alkansiya_balance, 0.01);
        $this->assertDatabaseCount('alkansiya_contributions', 0);
    }
}
