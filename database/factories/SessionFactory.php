<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use App\Models\HealthPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Session>
 */
class SessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'category' => 'private',
            'title' => $this->faker->sentence(3),
            'total_appointments' => 10,
            'completed_appointments' => 0,
            'total_value' => 1000.00,
            'paid_value' => 0.00,
            'start_date' => $this->faker->date(),
            'status' => 'Ativa',
            'observations' => $this->faker->paragraph(),
            'health_plan_id' => null,
        ];
    }
}
