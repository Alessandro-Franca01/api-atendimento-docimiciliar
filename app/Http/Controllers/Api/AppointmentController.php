<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
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

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $appointments = $query->orderBy('date')->orderBy('scheduled_time')->paginate(50);

        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'session_id' => 'nullable|exists:sessions,id',
            'date' => 'required|date',
            'scheduled_time' => 'required',
            'type' => 'required|in:Fisioterapia,Pilates,Avaliação,Reabilitação,Outro',
            'status' => 'nullable|in:Pendente,Confirmado,Realizado,Cancelado,Faltou',
            'observations' => 'nullable|string',
        ]);

        $validated['user_id'] = $request->user()->id;

        $appointment = Appointment::create($validated);

        return response()->json($appointment->load('patient'), 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return response()->json($appointment->load(['patient', 'session', 'payment']));
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
        ]);

        $appointment->update($validated);

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

        return response()->json($appointment);
    }
}
