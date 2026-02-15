<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
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
            'session_id' => Session::factory(),
            'date' => $this->faker->date(),
            'scheduled_time' => $this->faker->time('H:i'),
            'type' => 'Fisioterapia',
            'status' => 'Pendente',
            'observations' => $this->faker->sentence(),
            'category' => 'private',
        ];
    }
}
