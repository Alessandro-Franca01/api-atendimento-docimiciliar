<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Services\SessionService;

class SessionController extends Controller
{
    protected SessionService $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     *  Listar Sessoes
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Session::where('user_id', $request->user()->id)
            ->with(['patient', 'schedules'])
            ->withCount(['appointments as completed_appointments_count' => function ($query) {
                $query->whereIn('status', ['Realizado', 'Faltou']);
            }]);

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->latest()->paginate(10);

        return response()->json($sessions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'title' => 'nullable|string|max:255',
            'total_appointments' => 'required|integer|min:1|max:100',
            'total_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'observations' => 'nullable|string',
            'appointment_type' => 'nullable|in:Fisioterapia,Pilates,Avaliação,Reabilitação,Outro',
            'category' => 'in:private,clinic',
            'health_plan_id' => 'nullable|exists:health_plans,id',

            'schedules' => 'required|array|min:1',
            'schedules.*.day_of_week' => 'required|in:Segunda-feira,Terça-feira,Quarta-feira,Quinta-feira,Sexta-feira,Sábado,Domingo',
            'schedules.*.time' => 'required|date_format:H:i',

            'appointments' => 'sometimes|array',
            'appointments.*.date' => 'required_with:appointments|date',
            'appointments.*.time' => 'required_with:appointments|date_format:H:i',

             // Payment Validation
             'payment_amount' => 'nullable|numeric',
             'payment_method' => 'nullable|string',
             'is_paid' => 'nullable|boolean',
        ], [
            'schedules.required' => 'É necessário definir pelo menos um horário fixo.',
            'schedules.min' => 'É necessário definir pelo menos um horário fixo.',
            'total_appointments.max' => 'O número máximo de atendimentos por sessão é 100.',
            'start_date.after_or_equal' => 'A data de início não pode ser no passado.',
        ]);

        try {
            $session = $this->sessionService->createSessionWithAppointments(
                $validated,
                $request->user()->id
            );

            // Handle Payment Creation
            if ($request->has('payment_amount') && $request->input('payment_amount') !== null) {
                $totalAmount = (float) $request->input('payment_amount');
                $appointmentsCount = $session->appointments->count();

                if ($appointmentsCount > 0) {
                    $baseAmount = floor(($totalAmount / $appointmentsCount) * 100) / 100;
                    $remainder = round($totalAmount - ($baseAmount * $appointmentsCount), 2);

                    foreach ($session->appointments as $index => $appointment) {
                        $appointmentAmount = $baseAmount;
                        if ($index === 0) {
                            $appointmentAmount += $remainder;
                        }

                        Payment::create([
                            'user_id' => $request->user()->id,
                            'patient_id' => $validated['patient_id'],
                            'session_id' => $session->id,
                            'appointment_id' => $appointment->id,
                            'amount' => $appointmentAmount,
                            'payment_date' => $appointment->date, // Payment linked to appointment date
                            'payment_method' => $request->input('payment_method', 'Dinheiro'),
                            'status' => $request->boolean('is_paid') ? 'Pago' : 'Pendente',
                            'notes' => 'Gerado automaticamente via Sessão',
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Sessão criada com sucesso!',
                'session' => $session,
                'appointments_created' => $session->appointments->count(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar sessão.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Session $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return response()->json($session->load([
            'patient',
            'schedules',
            'appointments' => function ($query) {
                $query->orderBy('date')->orderBy('scheduled_time');
            },
            'payments'
        ]));
    }

    public function update(Request $request, Session $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'total_appointments' => 'sometimes|integer|min:1|max:100',
            'total_value' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'status' => 'sometimes|in:Ativa,Concluída,Cancelada',
            'observations' => 'nullable|string',
            'appointment_type' => 'nullable|in:Fisioterapia,Pilates,Avaliação,Reabilitação,Outro',

            'schedules' => 'sometimes|array',
            'schedules.*.day_of_week' => 'required_with:schedules|in:Segunda-feira,Terça-feira,Quarta-feira,Quinta-feira,Sexta-feira,Sábado,Domingo',
            'schedules.*.time' => 'required_with:schedules|date_format:H:i',

            'recalculate_appointments' => 'sometimes|boolean',
        ]);

        try {
            $session = $this->sessionService->updateSessionWithAppointments(
                $session,
                $validated,
                $request->user()->id
            );

            return response()->json([
                'message' => 'Sessão atualizada com sucesso!',
                'session' => $session,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar sessão.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, Session $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        // Não permitir excluir se já tiver atendimentos realizados
        if ($session->completed_appointments > 0) {
            return response()->json([
                'message' => 'Não é possível excluir uma sessão com atendimentos já realizados.'
            ], 422);
        }

        $session->delete();

        return response()->json([
            'message' => 'Sessão excluída com sucesso'
        ]);
    }

    public function previewAppointments(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'total_appointments' => 'required|integer|min:1|max:100',
            'schedules' => 'required|array|min:1',
            'schedules.*.day_of_week' => 'required|in:Segunda-feira,Terça-feira,Quarta-feira,Quinta-feira,Sexta-feira,Sábado,Domingo',
            'schedules.*.time' => 'required|date_format:H:i',
        ]);

        try {
            $appointments = $this->sessionService->previewAppointments($validated);

            return response()->json([
                'preview' => $appointments,
                'total' => count($appointments),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao gerar preview.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, Session $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $session->update([
            'status' => 'Cancelada',
            'end_date' => now(),
        ]);

        // Cancelar agendamentos pendentes
        $session->appointments()
            ->where('status', 'Pendente')
            ->update(['status' => 'Cancelado']);

        return response()->json([
            'message' => 'Sessão cancelada com sucesso',
            'session' => $session->load('appointments'),
        ]);
    }

    public function upcomingAppointments(Request $request, Session $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $appointments = $session->appointments()
            ->where('date', '>=', now()->format('Y-m-d'))
            ->whereIn('status', ['Pendente', 'Confirmado'])
            ->orderBy('date')
            ->orderBy('scheduled_time')
            ->get();

        return response()->json($appointments);
    }

    public function statistics(Request $request, Session $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return response()->json([
            'total_appointments' => $session->total_appointments,
            'completed_appointments' => $session->completed_appointments,
            'pending_appointments' => $session->appointments()->where('status', 'Pendente')->count(),
            'cancelled_appointments' => $session->appointments()->where('status', 'Cancelado')->count(),
            'total_value' => $session->total_value,
            'paid_value' => $session->paid_value,
            'remaining_balance' => $session->remaining_balance,
            'completion_percentage' => round(($session->completed_appointments / $session->total_appointments) * 100, 2),
            'payment_percentage' => round(($session->paid_value / $session->total_value) * 100, 2),
        ]);
    }
}
