<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Patient;
use App\Services\AssessmentService;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    private AssessmentService $assessmentService;

    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    public function index(Request $request, Patient $patient)
    {
        // Verify authorization
        if ($patient->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $assessments = Assessment::where('patient_id', $patient->id)
            ->with(['user', 'session'])
            ->latest()
            ->paginate(10);

        return response()->json($assessments);
    }

    public function store(Request $request, Patient $patient)
    {
        // Verify authorization
        if ($patient->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'session_id' => 'nullable|exists:sessions,id',
            'chief_complaint' => 'nullable|string',
            'duration' => 'nullable|string',
            'onset_type' => 'nullable|string',
            'pain_level' => 'nullable|integer|min:0|max:10',
            'previous_treatments' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'medications' => 'nullable|string',
            'surgery_procedures' => 'nullable|string',
            'rom_findings' => 'nullable|string',
            'strength_findings' => 'nullable|string',
            'balance_findings' => 'nullable|string',
            'gait_findings' => 'nullable|string',
            'adl_limitations' => 'nullable|string',
            'work_restrictions' => 'nullable|string',
            'posture_assessment' => 'nullable|string',
            'palpation_findings' => 'nullable|string',
            'special_tests_results' => 'nullable|string',
            'edema_findings' => 'nullable|string',
            'skin_condition' => 'nullable|string',
            'diagnostic_findings' => 'nullable|string',
            'diagnostic_notes' => 'nullable|string',
            'physio_diagnosis' => 'nullable|string',
            'prognosis' => 'nullable|string',
            'functional_limitations' => 'nullable|string',
            'treatment_goals_short' => 'nullable|string',
            'treatment_goals_long' => 'nullable|string',
            'recommended_frequency' => 'nullable|string',
            'estimated_duration' => 'nullable|string',
            'proposed_interventions' => 'nullable|string',
            'home_exercise_plan' => 'nullable|string',
            'additional_notes' => 'nullable|string',
        ]);

        $validated['patient_id'] = $patient->id;

        $assessment = $this->assessmentService->createAssessment(
            $validated,
            $request->user()->id
        );

        return response()->json($assessment->load(['user', 'session']), 201);
    }

    public function show(Request $request, Patient $patient, Assessment $assessment)
    {
        // Verify authorization
        if ($patient->user_id !== $request->user()->id || $assessment->patient_id !== $patient->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return response()->json($assessment->load(['user', 'patient', 'session']));
    }

    public function update(Request $request, Patient $patient, Assessment $assessment)
    {
        // Verify authorization
        if ($patient->user_id !== $request->user()->id || $assessment->patient_id !== $patient->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'session_id' => 'nullable|exists:sessions,id',
            'chief_complaint' => 'nullable|string',
            'duration' => 'nullable|string',
            'onset_type' => 'nullable|string',
            'pain_level' => 'nullable|integer|min:0|max:10',
            'previous_treatments' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'medications' => 'nullable|string',
            'surgery_procedures' => 'nullable|string',
            'rom_findings' => 'nullable|string',
            'strength_findings' => 'nullable|string',
            'balance_findings' => 'nullable|string',
            'gait_findings' => 'nullable|string',
            'adl_limitations' => 'nullable|string',
            'work_restrictions' => 'nullable|string',
            'posture_assessment' => 'nullable|string',
            'palpation_findings' => 'nullable|string',
            'special_tests_results' => 'nullable|string',
            'edema_findings' => 'nullable|string',
            'skin_condition' => 'nullable|string',
            'diagnostic_findings' => 'nullable|string',
            'diagnostic_notes' => 'nullable|string',
            'physio_diagnosis' => 'nullable|string',
            'prognosis' => 'nullable|string',
            'functional_limitations' => 'nullable|string',
            'treatment_goals_short' => 'nullable|string',
            'treatment_goals_long' => 'nullable|string',
            'recommended_frequency' => 'nullable|string',
            'estimated_duration' => 'nullable|string',
            'proposed_interventions' => 'nullable|string',
            'home_exercise_plan' => 'nullable|string',
            'additional_notes' => 'nullable|string',
        ]);

        $assessment = $this->assessmentService->updateAssessment($assessment, $validated);

        return response()->json($assessment->load(['user', 'patient', 'session']));
    }

    public function destroy(Request $request, Patient $patient, Assessment $assessment)
    {
        // Verify authorization
        if ($patient->user_id !== $request->user()->id || $assessment->patient_id !== $patient->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $assessment->delete();

        return response()->json(['message' => 'Avaliação deletada com sucesso']);
    }
}
