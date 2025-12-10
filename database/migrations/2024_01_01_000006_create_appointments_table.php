<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained()->onDelete('set null');
            $table->date('date');
            $table->time('scheduled_time');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('type', ['Fisioterapia', 'Pilates', 'Avaliação', 'Reabilitação', 'Outro'])->default('Fisioterapia');
            $table->enum('status', ['Pendente', 'Confirmado', 'Realizado', 'Cancelado', 'Faltou'])->default('Pendente');
            $table->text('observations')->nullable();
            $table->text('session_notes')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
