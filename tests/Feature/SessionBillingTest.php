<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\HealthPlan;
use App\Models\Patient;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SessionBillingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $patient;
    protected $healthPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->patient = Patient::factory()->create(['user_id' => $this->user->id]);
        $this->healthPlan = HealthPlan::factory()->create([
            'name' => 'Unimed',
            'value' => 50.00
        ]);
    }

    public function test_bills_session_when_last_appointment_executed()
    {
        $session = Session::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'category' => 'clinic',
            'total_appointments' => 2,
            'health_plan_id' => $this->healthPlan->id,
        ]);

        $app1 = Appointment::factory()->create([
            'session_id' => $session->id,
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'status' => 'Realizado',
            'category' => 'clinic',
            'health_plan_id' => $this->healthPlan->id,
        ]);

        $app2 = Appointment::factory()->create([
            'session_id' => $session->id,
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'status' => 'Pendente',
            'category' => 'clinic',
            'health_plan_id' => $this->healthPlan->id,
        ]);

        // Act: Execute last appointment
        $response = $this->actingAs($this->user)->putJson("/api/appointments/{$app2->id}/execute", [
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'Realizado',
        ]);

        $response->assertStatus(200);

        // Assert: Payment created
        $this->assertDatabaseHas('payments', [
            'session_id' => $session->id,
            'status' => 'Pendente',
            'amount' => 100.00, // 2 * 50
            'session_billing' => 1,
        ]);
    }

    public function test_bills_session_when_3_absences()
    {
        $session = Session::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'category' => 'clinic',
            'total_appointments' => 5,
            'health_plan_id' => $this->healthPlan->id,
        ]);

        // Create 2 executed (should be paid)
        Appointment::factory()->count(2)->create([
            'session_id' => $session->id,
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'status' => 'Realizado',
            'category' => 'clinic',
            'health_plan_id' => $this->healthPlan->id,
        ]);

        // Create 2 absences
        Appointment::factory()->count(2)->create([
            'session_id' => $session->id,
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'status' => 'Faltou',
            'category' => 'clinic',
            'health_plan_id' => $this->healthPlan->id,
        ]);

        // 5th appointment will be the 3rd absence
        $app3 = Appointment::factory()->create([
            'session_id' => $session->id,
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'status' => 'Pendente',
            'category' => 'clinic',
            'health_plan_id' => $this->healthPlan->id,
        ]);

        // Act: Update to Faltou
        $response = $this->actingAs($this->user)->putJson("/api/appointments/{$app3->id}", [
            'status' => 'Faltou',
        ]);

        $response->assertStatus(200);

        // Assert: Payment created
        // Only 2 Realizado, so 2 * 50 = 100
        $this->assertDatabaseHas('payments', [
            'session_id' => $session->id,
            'status' => 'Pendente',
            'amount' => 100.00,
            'session_billing' => 1,
        ]);
    }

    public function test_does_not_bill_private_session()
    {
        $session = Session::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'category' => 'private', // Private
            'total_appointments' => 1,
            'total_value' => 100,
        ]);

        $app1 = Appointment::factory()->create([
            'session_id' => $session->id,
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'status' => 'Pendente',
            'category' => 'private',
        ]);

        // Act
        $response = $this->actingAs($this->user)->putJson("/api/appointments/{$app1->id}/execute", [
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'Realizado',
        ]);

        $response->assertStatus(200);

        // Assert: No payment created
        $this->assertDatabaseMissing('payments', [
            'session_id' => $session->id,
            'session_billing' => 1,
        ]);
    }

    public function test_does_not_double_bill()
    {
        $session = Session::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'category' => 'clinic',
            'total_appointments' => 1,
            'health_plan_id' => $this->healthPlan->id,
        ]);

        // Already billed
        \App\Models\Payment::create([
             'patient_id' => $session->patient_id,
             'user_id' => $session->user_id,
             'session_id' => $session->id,
             'amount' => 50,
             'payment_date' => now(),
             'status' => 'Pendente',
             'session_billing' => true,
        ]);

         $app1 = Appointment::factory()->create([
            'session_id' => $session->id,
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'status' => 'Pendente',
            'category' => 'clinic',
             'health_plan_id' => $this->healthPlan->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)->putJson("/api/appointments/{$app1->id}/execute", [
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'Realizado',
        ]);

        $response->assertStatus(200);

        // Assert: Still only 1 payment
        $this->assertEquals(1, \App\Models\Payment::where('session_id', $session->id)->count());
    }
}
