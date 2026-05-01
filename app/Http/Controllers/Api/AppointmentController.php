<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Services\SessionService;

class AppointmentController extends Controller
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }
    public function index(Request $request)
    {
        $query = Appointment::where('user_id', $request->user()->id)
            ->with(['patient', 'session']);

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->has('sessionIsNull')) {
            $query->whereNull('session_id');
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $appointments = $query->orderBy('date')->orderBy('scheduled_time')->paginate(50);

        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        if($request->input('category') === 'clinic') {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'session_id' => 'nullable|exists:sessions,id',
                'date' => 'required|date',
                'scheduled_time' => 'required',
                'type' => 'required|in:Fisioterapia,Pilates,Avaliação,Reabilitação,Outro',
                'status' => 'nullable|in:Pendente,Confirmado,Realizado,Cancelado,Faltou',
                'observations' => 'nullable|string',
                'category' => 'nullable|in:private,clinic',
                'room' => 'nullable|in:no_room,room1,room2,room3,room4',
                'health_plan_id' => 'nullable|exists:health_plans,id',
                // Payment Validation
                'payment_amount' => 'nullable|numeric',
                'payment_method' => 'nullable|string',
                'is_paid' => 'nullable|boolean',
            ]);
        } elseif ($request->input('category') === 'private') {
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'session_id' => 'nullable|exists:sessions,id',
                'date' => 'required|date',
                'scheduled_time' => 'required',
                'type' => 'required|in:Fisioterapia,Pilates,Avaliação,Reabilitação,Outro',
                'status' => 'nullable|in:Pendente,Confirmado,Realizado,Cancelado,Faltou',
                'observations' => 'nullable|string',
                'category' => 'nullable|in:private,clinic',
                // Payment Validation
                'payment_amount' => 'nullable|numeric',
                'payment_method' => 'nullable|string',
                'is_paid' => 'nullable|boolean',
            ]);
        }

        $validated['user_id'] = $request->user()->id;

        try {
            DB::beginTransaction();
            $appointment = Appointment::create($validated);

            // Handle Payment Creation
            if ($request->has('payment_amount')) {
                $paymentData = [
                    'user_id' => $request->user()->id, // Add user_id here
                    'patient_id' => $validated['patient_id'],
                    'appointment_id' => $appointment->id,
                    'session_id' => $validated['session_id'] ?? null,
                    'amount' => $request->input('payment_amount') ?? 0,
                    'payment_date' => $validated['date'],
                    'payment_method' => $request->input('payment_method', 'Dinheiro'),
                    'status' => $request->boolean('is_paid') ? 'Pago' : 'Pendente',
                    'notes' => 'Gerado automaticamente via Agendamento',
                ];

                Payment::create($paymentData);
                DB::commit();
            }
        }catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'message' => 'Erro ao criar Atendimento.',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json($appointment->load('patient'), 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return response()->json($appointment->load(['patient', 'session', 'payment', 'healthPlan']));
    }

    public function update(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'date' => 'sometimes|date',
            'scheduled_time' => 'sometimes',
            'start_time' => 'sometimes',
            'end_time' => 'sometimes',
            'type' => 'sometimes|in:Fisioterapia,Pilates,Avaliação,Reabilitação,Outro',
            'status' => 'sometimes|in:Pendente,Confirmado,Realizado,Cancelado,Faltou',
            'observations' => 'nullable|string',
            'session_notes' => 'nullable|string',
            'session_notes' => 'nullable|string',
            'category' => 'nullable|in:private,clinic',
            'health_plan_id' => 'nullable|exists:health_plans,id',
        ]);

        $appointment->update($validated);

        if ($appointment->session_id) {
            $this->sessionService->checkAndBillSession($appointment->session);
        }

        return response()->json($appointment->load('patient'));
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $appointment->delete();

        return response()->json(['message' => 'Atendimento excluído com sucesso']);
    }

    public function checkIn(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $appointment->update([
            'start_time' => now()->format('H:i'),
            'status' => 'Confirmado',
        ]);

        return response()->json($appointment);
    }

    public function checkOut(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $appointment->update([
            'end_time' => now()->format('H:i'),
            'status' => 'Realizado',
        ]);

        if ($appointment->session_id) {
            $this->sessionService->checkAndBillSession($appointment->session);
        }

        return response()->json($appointment->load(['patient', 'session']));
    }

    public function execute(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'start_time' => 'required',
            'end_time' => 'required',
            'session_notes' => 'nullable|string',
            'status' => 'required|in:Realizado',
            'resources' => 'nullable|array',
        ]);

        $appointment->update($validated);

        if ($appointment->session_id) {
            $this->sessionService->checkAndBillSession($appointment->session);
        }

        return response()->json($appointment->load(['patient', 'session']));
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:Pendente,Confirmado,Realizado,Cancelado,Faltou',
        ]);

        $appointment->update([
            'status' => $validated['status']
        ]);

        if ($appointment->session_id) {
            $this->sessionService->checkAndBillSession($appointment->session);
        }

        return response()->json($appointment->load(['patient', 'session']));
    }
}
