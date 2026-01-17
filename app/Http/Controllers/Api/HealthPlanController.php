<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthPlan;
use Illuminate\Http\Request;

class HealthPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $healthPlans = HealthPlan::all();
        return response()->json($healthPlans);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        $healthPlan = HealthPlan::create($validatedData);

        return response()->json($healthPlan, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(HealthPlan $healthPlan)
    {
        return response()->json($healthPlan);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HealthPlan $healthPlan)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'value' => 'sometimes|numeric',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $healthPlan->update($validatedData);

        return response()->json($healthPlan);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HealthPlan $healthPlan)
    {
        $healthPlan->delete();
        return response()->json(null, 204);
    }
}
