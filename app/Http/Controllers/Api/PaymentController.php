<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::where('user_id', $request->user()->id)
            ->with(['patient', 'appointment', 'session']);

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        $totalCollected = $query->clone()->where('status', 'Pago')->sum('amount');
        $totalPending = $query->clone()->where('status', 'Pendente')->sum('amount');
        
        $payments = $query->latest('payment_date')->paginate(10);

        $responseData = $payments->toArray();
        $responseData['total_collected'] = $totalCollected;
        $responseData['total_pending'] = $totalPending;

        return response()->json($responseData);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'session_id' => 'nullable|exists:sessions,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:Pix,Dinheiro,Cartao,Debito,Gratuito',
            'status' => 'nullable|in:Pago,Pendente,Atrasado,Cancelado,Acao_Social',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'Pago';

        $payment = Payment::create($validated);

        return response()->json($payment->load('patient'), 201);
    }

    public function show(Request $request, Payment $payment)
    {
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return response()->json($payment->load(['patient', 'appointment', 'session']));
    }

    public function update(Request $request, Payment $payment)
    {
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'payment_date' => 'sometimes|date',
            'payment_method' => 'sometimes|in:Pix,Dinheiro,Cartao,Debito,Gratuito',
            'status' => 'sometimes|in:Pago,Pendente,Atrasado,Cancelado,Acao_Social',
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validated);

        return response()->json($payment);
    }

    public function destroy(Request $request, Payment $payment)
    {
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $payment->delete();

        return response()->json(['message' => 'Pagamento excluído com sucesso']);
    }
}
