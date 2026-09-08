<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user assigned to the given spatie role, creating the role
     * (and clearing the permission cache) if it does not exist yet.
     */
    protected function userWithRole(string $role, array $attributes = []): User
    {
        Role::findOrCreate($role, 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    protected function admin(array $attributes = []): User
    {
        return $this->userWithRole('admin', $attributes);
    }
}
