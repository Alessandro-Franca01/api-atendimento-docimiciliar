<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Session;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $query = Session::where('user_id', $request->user()->id)
            ->with(['patient', 'schedules']);

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->latest()->paginate(15);

        return response()->json($sessions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'title' => 'nullable|string',
            'total_appointments' => 'required|integer|min:1',
            'total_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'observations' => 'nullable|string',
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => 'required|in:Segunda-feira,Terça-feira,Quarta-feira,Quinta-feira,Sexta-feira,Sábado,Domingo',
            'schedules.*.time' => 'required',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'Ativa';

        $session = Session::create($validated);

        if ($request->has('schedules')) {
            foreach ($request->schedules as $schedule) {
                $session->schedules()->create($schedule);
            }
        }

        return response()->json($session->load(['patient', 'schedules']), 201);
    }

    public function show(Request $request, Session $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return response()->json($session->load([
            'patient',
            'schedules',
            'appointments',
            'payments'
        ]));
    }

    public function update(Request $request, Session $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string',
            'total_appointments' => 'sometimes|integer|min:1',
            'total_value' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'status' => 'sometimes|in:Ativa,Concluída,Cancelada',
            'observations' => 'nullable|string',
        ]);

        $session->update($validated);

        return response()->json($session->load('schedules'));
    }

    public function destroy(Request $request, Session $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $session->delete();

        return response()->json(['message' => 'Sessão excluída com sucesso']);
    }
}
