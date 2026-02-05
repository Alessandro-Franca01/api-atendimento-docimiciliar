<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'user_id',
        'session_id',
        'chief_complaint',
        'duration',
        'onset_type',
        'pain_level',
        'previous_treatments',
        'medical_history',
        'medications',
        'surgery_procedures',
        'rom_findings',
        'strength_findings',
        'balance_findings',
        'gait_findings',
        'adl_limitations',
        'work_restrictions',
        'posture_assessment',
        'palpation_findings',
        'special_tests_results',
        'edema_findings',
        'skin_condition',
        'diagnostic_findings',
        'diagnostic_notes',
        'physio_diagnosis',
        'prognosis',
        'functional_limitations',
        'treatment_goals_short',
        'treatment_goals_long',
        'recommended_frequency',
        'estimated_duration',
        'proposed_interventions',
        'home_exercise_plan',
        'additional_notes',
    ];

    protected $casts = [
        'pain_level' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }
}
