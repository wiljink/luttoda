<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Services\MemberSanctionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MemberSanctionTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_column_accepts_suspended_and_terminated(): void
    {
        $member = Member::factory()->create(['status' => 'suspended']);
        $this->assertSame('suspended', $member->fresh()->status);

        $member->update(['status' => 'terminated']);
        $this->assertSame('terminated', $member->fresh()->status);
    }

    public function test_recording_a_suspension_sanction_suspends_the_member(): void
    {
        $member = Member::factory()->create(['status' => 'active']);
        $until = today()->addDays(30);

        $this->actingAs($this->admin())
            ->post(route('members.violations.store', $member), [
                'violation_date' => today()->toDateString(),
                'type' => 'Reckless driving',
                'sanction' => 'suspension',
                'sanction_until' => $until->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $member->refresh();
        $this->assertSame('suspended', $member->status);
        $this->assertTrue($member->suspended_until->isSameDay($until));
        $this->assertTrue($member->isUnderSanction());
    }

    public function test_termination_and_dismembership_terminate_the_member(): void
    {
        foreach (['termination', 'dismembership'] as $sanction) {
            $member = Member::factory()->create(['status' => 'active']);

            $this->actingAs($this->admin())
                ->post(route('members.violations.store', $member), [
                    'violation_date' => today()->toDateString(),
                    'type' => 'Serious offense',
                    'sanction' => $sanction,
                ])
                ->assertSessionHasNoErrors();

            $this->assertSame('terminated', $member->fresh()->status);
            $this->assertNull($member->fresh()->suspended_until);
        }
    }

    public function test_violation_without_sanction_leaves_status_untouched(): void
    {
        $member = Member::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin())
            ->post(route('members.violations.store', $member), [
                'violation_date' => today()->toDateString(),
                'type' => 'Late payment',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('active', $member->fresh()->status);
    }

    public function test_suspension_requires_an_until_date(): void
    {
        $member = Member::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin())
            ->post(route('members.violations.store', $member), [
                'violation_date' => today()->toDateString(),
                'type' => 'Reckless driving',
                'sanction' => 'suspension',
            ])
            ->assertSessionHasErrors('sanction_until');

        $this->assertSame('active', $member->fresh()->status);
    }

    public function test_lift_expired_suspensions_reactivates_and_stamps_the_violation(): void
    {
        $member = Member::factory()->create(['status' => 'active']);

        // Suspend with an until-date already in the past.
        $violation = $member->violations()->create([
            'violation_date' => today()->subMonth(),
            'type' => 'Reckless driving',
            'sanction' => 'suspension',
            'sanction_until' => today()->subDay(),
        ]);
        $member->update(['status' => 'suspended', 'suspended_until' => today()->subDay()]);

        $reactivated = app(MemberSanctionService::class)->liftExpired(Carbon::today());

        $this->assertSame(1, $reactivated);
        $this->assertSame('active', $member->fresh()->status);
        $this->assertNull($member->fresh()->suspended_until);
        $this->assertNotNull($violation->fresh()->sanction_lifted_at);
    }

    public function test_lift_expired_leaves_future_suspensions_alone(): void
    {
        $member = Member::factory()->create([
            'status' => 'suspended',
            'suspended_until' => today()->addWeek(),
        ]);

        $this->assertSame(0, app(MemberSanctionService::class)->liftExpired());
        $this->assertSame('suspended', $member->fresh()->status);
    }

    public function test_member_can_be_created_as_non_member(): void
    {
        $this->actingAs($this->admin())
            ->post(route('members.store'), [
                'member_no' => 'MBR-5555',
                'firstname' => 'Juan',
                'lastname' => 'Dela Cruz',
                'plate_number' => 'XYZ-9999',
                'operator_name' => 'Juan Dela Cruz',
                'route' => 'Carmen',
                'category' => 'non-member',
                'date_joined' => today()->toDateString(),
                'status' => 'active',
            ])
            ->assertRedirect(route('members.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('members', ['member_no' => 'MBR-5555', 'category' => 'non-member']);
    }
}
