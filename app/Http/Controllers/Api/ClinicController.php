<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clinics = Clinic::all();
        return response()->json($clinics);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cnpj' => 'nullable|string|unique:clinics,cnpj|max:18',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:20',
        ]);

        $clinic = Clinic::create($validated);

        return response()->json($clinic, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Clinic $clinic)
    {
        return response()->json($clinic->load(['users', 'addresses']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Clinic $clinic)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'cnpj' => 'nullable|string|max:18|unique:clinics,cnpj,' . $clinic->id,
            'email' => 'sometimes|string|email|max:255',
            'phone' => 'sometimes|string|max:20',
        ]);

        $clinic->update($validated);

        return response()->json($clinic);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Clinic $clinic)
    {
        $clinic->delete();

        return response()->json(null, 204);
    }
}
