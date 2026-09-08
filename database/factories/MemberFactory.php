<?php

namespace Database\Factories;

use App\Models\Member;
use App\Support\FilipinoNames;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female']);

        return [
            'member_no' => 'MBR-'.$this->faker->unique()->numberBetween(1000, 9999),
            'firstname' => FilipinoNames::firstName($gender),
            'lastname' => FilipinoNames::lastName(),
            'middlename' => FilipinoNames::lastName(),
            'plate_number' => strtoupper($this->faker->bothify('???-####')),
            'operator_name' => FilipinoNames::fullName(),
            'route' => $this->faker->randomElement(['Carmen', 'Cogon']),
            'category' => 'member',
            'contact_number' => $this->faker->numerify('09#########'),
            'address' => $this->faker->address(),
            'date_joined' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'status' => 'active',
            'savings_balance' => 0,
        ];
    }
}
