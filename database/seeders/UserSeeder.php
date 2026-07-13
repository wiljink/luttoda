<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@luttoda.test'],
            ['name' => 'Admin', 'password' => Hash::make('password')]
        );
        $admin->assignRole('admin');

        $collector = User::firstOrCreate(
            ['email' => 'collector@luttoda.test'],
            ['name' => 'Collector 1', 'password' => Hash::make('password')]
        );
        $collector->assignRole('collector');
    }
}
