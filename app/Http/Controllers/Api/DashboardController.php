<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Estatísticas financeiras
        $totalToPay = Payment::where('user_id', $userId)
            ->whereIn('status', ['Pendente', 'Atrasado'])
            ->sum('amount');

        $monthlyRevenue = Payment::where('user_id', $userId)
            ->where('status', 'Pago')
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Próximos atendimentos
        $upcomingAppointments = Appointment::where('user_id', $userId)
            ->where('date', '>=', $today)
            ->whereIn('status', ['Pendente', 'Confirmado'])
            ->with('patient')
            ->orderBy('date')
            ->orderBy('scheduled_time')
            ->limit(5)
            ->get();

        // Atendimentos pendentes de registro
        $pendingAppointments = Appointment::where('user_id', $userId)
            ->where('date', '<', $today)
            ->where('status', 'Pendente')
            ->with('patient')
            ->latest('date')
            ->limit(5)
            ->get();

        return response()->json([
            'financial' => [
                'total_to_receive' => $totalToPay,
                'monthly_revenue' => $monthlyRevenue,
            ],
            'upcoming_appointments' => $upcomingAppointments,
            'pending_appointments' => $pendingAppointments,
        ]);
    }
}
