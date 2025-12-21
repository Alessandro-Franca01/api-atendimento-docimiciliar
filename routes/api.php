<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AddressController;

// Rotas públicas
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Patients
    Route::apiResource('patients', PatientController::class);
    Route::get('/patients/{patient}/financial', [PatientController::class, 'financialSummary']);

    // Addresses
    Route::post('/patients/{patient}/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

    // Appointments
    Route::apiResource('appointments', AppointmentController::class);
    Route::post('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn']);
    Route::post('/appointments/{appointment}/check-out', [AppointmentController::class, 'checkOut']);
    Route::post('/appointments/{appointment}/execute', [AppointmentController::class, 'execute']);

    // Sessions
    Route::apiResource('sessions', SessionController::class);
    Route::post('/sessions/preview-appointments', [SessionController::class, 'previewAppointments']);
    Route::post('/sessions/{session}/cancel', [SessionController::class, 'cancel']);
    Route::get('/sessions/{session}/upcoming', [SessionController::class, 'upcomingAppointments']);
    Route::get('/sessions/{session}/statistics', [SessionController::class, 'statistics']);

    // Payments
    Route::apiResource('payments', PaymentController::class);
});
