<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'member_no' => 'MBR-' . $this->faker->unique()->numberBetween(1000, 9999),
            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'middlename' => $this->faker->lastName(),
            'plate_number' => strtoupper($this->faker->bothify('???-####')),
            'operator_name' => $this->faker->name(),
            'route' => $this->faker->randomElement(['Carmen', 'Cogon']),
            'contact_number' => $this->faker->numerify('09#########'),
            'address' => $this->faker->address(),
            'date_joined' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'status' => 'active',
            'savings_balance' => 0,
        ];
    }
}
