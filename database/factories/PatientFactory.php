<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'cpf' => $this->faker->numerify('###########'),
            'birth_date' => $this->faker->date(),
            'phone' => $this->faker->phoneNumber(),
            'status' => 'Ativo',
            'occupation' => $this->faker->jobTitle(),
        ];
    }
}
