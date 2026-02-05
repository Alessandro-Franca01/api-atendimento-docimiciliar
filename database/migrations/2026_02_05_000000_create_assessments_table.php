<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('sessions')->onDelete('set null');

            // Patient History Section
            $table->text('chief_complaint')->nullable();
            $table->string('duration')->nullable();
            $table->string('onset_type')->nullable();
            $table->integer('pain_level')->nullable();
            $table->text('previous_treatments')->nullable();
            $table->text('medical_history')->nullable();
            $table->text('medications')->nullable();
            $table->text('surgery_procedures')->nullable();

            // Functional Assessment Section
            $table->text('rom_findings')->nullable();
            $table->text('strength_findings')->nullable();
            $table->text('balance_findings')->nullable();
            $table->text('gait_findings')->nullable();
            $table->text('adl_limitations')->nullable();
            $table->text('work_restrictions')->nullable();

            // Physical Examination Section
            $table->text('posture_assessment')->nullable();
            $table->text('palpation_findings')->nullable();
            $table->text('special_tests_results')->nullable();
            $table->text('edema_findings')->nullable();
            $table->text('skin_condition')->nullable();

            // Diagnostic Section
            $table->text('diagnostic_findings')->nullable();
            $table->text('diagnostic_notes')->nullable();

            // Assessment & Diagnosis Section
            $table->text('physio_diagnosis')->nullable();
            $table->text('prognosis')->nullable();
            $table->text('functional_limitations')->nullable();

            // Treatment Plan Section
            $table->text('treatment_goals_short')->nullable();
            $table->text('treatment_goals_long')->nullable();
            $table->string('recommended_frequency')->nullable();
            $table->string('estimated_duration')->nullable();
            $table->text('proposed_interventions')->nullable();
            $table->text('home_exercise_plan')->nullable();

            // Additional
            $table->text('additional_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('user_id');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
