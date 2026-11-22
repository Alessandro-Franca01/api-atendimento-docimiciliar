<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $open = ['Pendente', 'Atrasado'];

        // Listagem leve: agrega o financeiro no SQL em vez de carregar
        // todas as sessões e pagamentos de cada paciente.
        $query = Patient::where('user_id', $request->user()->id)
            ->with('addresses')
            ->withSum(['payments as total_to_pay_sum' => fn ($q) => $q->whereIn('status', $open)], 'amount')
            ->withSum(['payments as total_paid_sum' => fn ($q) => $q->where('status', 'Pago')], 'amount')
            ->withCount([
                'payments as pending_payments_count' => fn ($q) => $q->whereIn('status', $open),
                'payments as overdue_payments_count' => fn ($q) => $q->where('status', 'Atrasado'),
            ]);

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('cpf', 'like', '%' . $request->search . '%')
                  ->orWhere('rg', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $patients = $query->latest()->paginate($perPage)->through(function (Patient $p) {
            $data = $p->toArray();
            $pending = $data['pending_payments_count'] ?? 0;
            $overdue = $data['overdue_payments_count'] ?? 0;
            $data['total_to_pay'] = (float) ($data['total_to_pay_sum'] ?? 0);
            $data['total_paid'] = (float) ($data['total_paid_sum'] ?? 0);
            $data['financial_status'] = $pending === 0 ? 'Em dia' : ($overdue > 0 ? 'Atrasado' : 'Pendente');
            unset($data['total_to_pay_sum'], $data['total_paid_sum'], $data['pending_payments_count'], $data['overdue_payments_count']);
            return $data;
        });

        return response()->json($patients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:patients,email',
            'phone' => 'required|string',
            'cpf' => 'nullable|string|unique:patients,cpf',
            'rg' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'age' => 'nullable|integer',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'status' => 'nullable|in:Ativo,Inativo',
            'notes' => 'nullable|string',
            'gender' => 'nullable|string',
            'occupation' => 'nullable|string',
            'addresses' => 'nullable|array',
        ]);

        $validated['user_id'] = $request->user()->id;

        $patient = Patient::create($validated);

        if ($request->has('addresses')) {
            foreach ($request->addresses as $address) {
                $patient->addresses()->create($address);
            }
        }

        return response()->json($patient->load('addresses'), 201);
    }

    public function show(Request $request, Patient $patient)
    {
        if ($patient->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return response()->json($patient->load([
            'addresses',
            'sessions.schedules',
            'appointments' => function ($query) {
                $query->latest('date')->limit(10);
            },
            'payments' => function ($query) {
                $query->latest('payment_date')->limit(10);
            },
            'assessments' => function ($query) {
                $query->latest()->limit(10);
            },
        ]));
    }

    public function update(Request $request, Patient $patient)
    {
        if ($patient->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:patients,email,' . $patient->id,
            'phone' => 'sometimes|string',
            'cpf' => 'sometimes|string|unique:patients,cpf,' . $patient->id,
            'rg' => 'sometimes|string',
            'birth_date' => 'sometimes|date',
            'age' => 'sometimes|integer',
            'emergency_contact_name' => 'sometimes|string',
            'emergency_contact_phone' => 'sometimes|string',
            'status' => 'sometimes|in:Ativo,Inativo',
            'notes' => 'sometimes|string',
            'gender' => 'sometimes|string',
            'occupation' => 'sometimes|string',
        ]);

        $patient->update($validated);

        return response()->json($patient->load('addresses'));
    }

    public function destroy(Request $request, Patient $patient)
    {
        if ($patient->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $patient->delete();

        return response()->json(['message' => 'Paciente excluído com sucesso']);
    }

    public function financialSummary(Request $request, Patient $patient)
    {
        if ($patient->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return response()->json([
            'total_to_pay' => $patient->total_to_pay,
            'total_paid' => $patient->total_paid,
            'financial_status' => $patient->financial_status,
        ]);
    }
}
