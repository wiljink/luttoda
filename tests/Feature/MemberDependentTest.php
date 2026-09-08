<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDependentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_and_remove_a_dependent(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('members.dependents.store', $member), [
                'name' => 'Maria Cruz',
                'relationship' => 'spouse',
                'birthdate' => '1990-05-01',
            ])
            ->assertRedirect(route('members.show', $member))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('member_dependents', [
            'member_id' => $member->id,
            'name' => 'Maria Cruz',
            'relationship' => 'spouse',
            'active' => true,
        ]);

        $dependent = $member->dependents()->first();

        $this->actingAs($this->admin())
            ->delete(route('members.dependents.destroy', [$member, $dependent]))
            ->assertRedirect(route('members.show', $member));

        $this->assertDatabaseMissing('member_dependents', ['id' => $dependent->id]);
    }

    public function test_dependents_are_deleted_with_their_member(): void
    {
        $member = Member::factory()->create();
        $member->dependents()->create(['name' => 'Kid', 'relationship' => 'child']);

        $member->forceDelete();

        $this->assertDatabaseCount('member_dependents', 0);
    }

    public function test_cannot_edit_a_dependent_of_another_member(): void
    {
        $a = Member::factory()->create();
        $b = Member::factory()->create();
        $dependent = $b->dependents()->create(['name' => 'Kid', 'relationship' => 'child']);

        $this->actingAs($this->admin())
            ->delete(route('members.dependents.destroy', [$a, $dependent]))
            ->assertNotFound();
    }
}
