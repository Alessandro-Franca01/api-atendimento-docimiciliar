<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class SessionService
{
    public function createSessionWithAppointments(array $data, int $userId): Session
    {
        DB::beginTransaction();

        try {
            $session = Session::create([
                'patient_id' => $data['patient_id'],
                'user_id' => $userId,
                'title' => $data['title'] ?? null,
                'total_appointments' => $data['total_appointments'],
                'completed_appointments' => 0,
                'total_value' => $data['total_value'],
                'paid_value' => 0,
                'start_date' => $data['start_date'],
                'status' => 'Ativa',
                'observations' => $data['observations'] ?? null,
            ]);

            $schedules = [];
            if (isset($data['schedules']) && is_array($data['schedules'])) {
                foreach ($data['schedules'] as $schedule) {
                    $schedules[] = $session->schedules()->create([
                        'day_of_week' => $schedule['day_of_week'],
                        'time' => $schedule['time'],
                    ]);
                }
            }

            if (!empty($schedules)) {
                $appointments = $this->generateAppointments(
                    $session,
                    $schedules,
                    $data['start_date'],
                    $data['total_appointments']
                );

                foreach ($appointments as $appointmentData) {
                    Appointment::create([
                        'patient_id' => $session->patient_id,
                        'user_id' => $userId,
                        'session_id' => $session->id,
                        'date' => $appointmentData['date'],
                        'scheduled_time' => $appointmentData['time'],
                        'type' => $data['appointment_type'] ?? 'Fisioterapia',
                        'status' => 'Pendente',
                        'observations' => "Sessão: {$session->title}",
                    ]);
                }
            }

            DB::commit();
            return $session->load(['schedules', 'appointments', 'patient']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function generateAppointments(Session $session, array $schedules, string $startDate, int $totalAppointments): array
    {
        $appointments = [];
        $currentDate = Carbon::parse($startDate);
        $appointmentsGenerated = 0;

        $dayMapping = [
            'Domingo' => 0, 'Segunda-feira' => 1, 'Terça-feira' => 2,
            'Quarta-feira' => 3, 'Quinta-feira' => 4, 'Sexta-feira' => 5, 'Sábado' => 6,
        ];

        usort($schedules, function ($a, $b) use ($dayMapping) {
            $dayA = $dayMapping[$a->day_of_week] ?? 0;
            $dayB = $dayMapping[$b->day_of_week] ?? 0;
            return $dayA === $dayB ? strcmp($a->time, $b->time) : $dayA <=> $dayB;
        });

        $maxIterations = 365;
        $iterations = 0;

        while ($appointmentsGenerated < $totalAppointments && $iterations < $maxIterations) {
            $currentDayOfWeek = $currentDate->dayOfWeek;

            foreach ($schedules as $schedule) {
                if ($currentDayOfWeek === ($dayMapping[$schedule->day_of_week] ?? -1)) {
                    $appointments[] = [
                        'date' => $currentDate->format('Y-m-d'),
                        'time' => $schedule->time,
                        'day_of_week' => $schedule->day_of_week,
                    ];

                    $appointmentsGenerated++;
                    if ($appointmentsGenerated >= $totalAppointments) break 2;
                }
            }

            $currentDate->addDay();
            $iterations++;
        }

        return $appointments;
    }

    public function previewAppointments(array $data): array
    {
        $schedules = collect($data['schedules'] ?? [])->map(function($schedule) {
            return (object) [
                'day_of_week' => $schedule['day_of_week'],
                'time' => $schedule['time'],
            ];
        })->all();

        $tempSession = new Session(['start_date' => $data['start_date']]);

        return $this->generateAppointments(
            $tempSession,
            $schedules,
            $data['start_date'],
            $data['total_appointments']
        );
    }
}
