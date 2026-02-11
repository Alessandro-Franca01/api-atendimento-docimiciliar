<?php

namespace App\Services;

use App\Models\Assessment;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class AssessmentService
{
    public function createAssessment(array $data, int $userId): Assessment
    {
        DB::beginTransaction();
        try {
            $assessmentData = [
                'patient_id' => $data['patient_id'],
                'user_id' => $userId,
                'session_id' => $data['session_id'] ?? null,
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'duration' => $data['duration'] ?? null,
                'onset_type' => $data['onset_type'] ?? null,
                'pain_level' => $data['pain_level'] ?? null,
                'previous_treatments' => $data['previous_treatments'] ?? null,
                'medical_history' => $data['medical_history'] ?? null,
                'medications' => $data['medications'] ?? null,
                'surgery_procedures' => $data['surgery_procedures'] ?? null,
                'rom_findings' => $data['rom_findings'] ?? null,
                'strength_findings' => $data['strength_findings'] ?? null,
                'balance_findings' => $data['balance_findings'] ?? null,
                'gait_findings' => $data['gait_findings'] ?? null,
                'adl_limitations' => $data['adl_limitations'] ?? null,
                'work_restrictions' => $data['work_restrictions'] ?? null,
                'posture_assessment' => $data['posture_assessment'] ?? null,
                'palpation_findings' => $data['palpation_findings'] ?? null,
                'special_tests_results' => $data['special_tests_results'] ?? null,
                'edema_findings' => $data['edema_findings'] ?? null,
                'skin_condition' => $data['skin_condition'] ?? null,
                'diagnostic_findings' => $data['diagnostic_findings'] ?? null,
                'diagnostic_notes' => $data['diagnostic_notes'] ?? null,
                'physio_diagnosis' => $data['physio_diagnosis'] ?? null,
                'prognosis' => $data['prognosis'] ?? null,
                'functional_limitations' => $data['functional_limitations'] ?? null,
                'treatment_goals_short' => $data['treatment_goals_short'] ?? null,
                'treatment_goals_long' => $data['treatment_goals_long'] ?? null,
                'recommended_frequency' => $data['recommended_frequency'] ?? null,
                'estimated_duration' => $data['estimated_duration'] ?? null,
                'proposed_interventions' => $data['proposed_interventions'] ?? null,
                'home_exercise_plan' => $data['home_exercise_plan'] ?? null,
                'additional_notes' => $data['additional_notes'] ?? null,
            ];

            $assessment = Assessment::create($assessmentData);

            DB::commit();

            return $assessment;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar avaliação fisioterapêutica: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateAssessment(Assessment $assessment, array $data): Assessment
    {
        DB::beginTransaction();
        try {
            $updateData = [
                'chief_complaint' => $data['chief_complaint'] ?? $assessment->chief_complaint,
                'duration' => $data['duration'] ?? $assessment->duration,
                'onset_type' => $data['onset_type'] ?? $assessment->onset_type,
                'pain_level' => $data['pain_level'] ?? $assessment->pain_level,
                'previous_treatments' => $data['previous_treatments'] ?? $assessment->previous_treatments,
                'medical_history' => $data['medical_history'] ?? $assessment->medical_history,
                'medications' => $data['medications'] ?? $assessment->medications,
                'surgery_procedures' => $data['surgery_procedures'] ?? $assessment->surgery_procedures,
                'rom_findings' => $data['rom_findings'] ?? $assessment->rom_findings,
                'strength_findings' => $data['strength_findings'] ?? $assessment->strength_findings,
                'balance_findings' => $data['balance_findings'] ?? $assessment->balance_findings,
                'gait_findings' => $data['gait_findings'] ?? $assessment->gait_findings,
                'adl_limitations' => $data['adl_limitations'] ?? $assessment->adl_limitations,
                'work_restrictions' => $data['work_restrictions'] ?? $assessment->work_restrictions,
                'posture_assessment' => $data['posture_assessment'] ?? $assessment->posture_assessment,
                'palpation_findings' => $data['palpation_findings'] ?? $assessment->palpation_findings,
                'special_tests_results' => $data['special_tests_results'] ?? $assessment->special_tests_results,
                'edema_findings' => $data['edema_findings'] ?? $assessment->edema_findings,
                'skin_condition' => $data['skin_condition'] ?? $assessment->skin_condition,
                'diagnostic_findings' => $data['diagnostic_findings'] ?? $assessment->diagnostic_findings,
                'diagnostic_notes' => $data['diagnostic_notes'] ?? $assessment->diagnostic_notes,
                'physio_diagnosis' => $data['physio_diagnosis'] ?? $assessment->physio_diagnosis,
                'prognosis' => $data['prognosis'] ?? $assessment->prognosis,
                'functional_limitations' => $data['functional_limitations'] ?? $assessment->functional_limitations,
                'treatment_goals_short' => $data['treatment_goals_short'] ?? $assessment->treatment_goals_short,
                'treatment_goals_long' => $data['treatment_goals_long'] ?? $assessment->treatment_goals_long,
                'recommended_frequency' => $data['recommended_frequency'] ?? $assessment->recommended_frequency,
                'estimated_duration' => $data['estimated_duration'] ?? $assessment->estimated_duration,
                'proposed_interventions' => $data['proposed_interventions'] ?? $assessment->proposed_interventions,
                'home_exercise_plan' => $data['home_exercise_plan'] ?? $assessment->home_exercise_plan,
                'additional_notes' => $data['additional_notes'] ?? $assessment->additional_notes,
            ];

            $assessment->update($updateData);

            DB::commit();

            return $assessment;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar avaliação fisioterapêutica: ' . $e->getMessage());
            throw $e;
        }
    }
}
