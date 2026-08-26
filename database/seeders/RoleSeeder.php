<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'collector', 'accounting'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Example test users — adjust or remove for production
        $admin = User::firstOrCreate(
            ['email' => 'admin@luttoda.test'],
            ['name' => 'Admin User', 'password' => Hash::make('password')]
        );
        $admin->assignRole('admin');

        $collector = User::firstOrCreate(
            ['email' => 'collector1@luttoda.test'],
            ['name' => 'Collector 1', 'password' => Hash::make('password')]
        );
        $collector->assignRole('collector');

        $accounting = User::firstOrCreate(
            ['email' => 'accounting1@luttoda.test'],
            ['name' => 'Accounting 1', 'password' => Hash::make('password')]
        );
        $accounting->assignRole('accounting');
    }
}
