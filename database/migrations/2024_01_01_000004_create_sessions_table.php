<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->integer('total_appointments');
            $table->integer('completed_appointments')->default(0);
            $table->decimal('total_value', 10, 2);
            $table->decimal('paid_value', 10, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['Ativa', 'Concluída', 'Cancelada'])->default('Ativa');
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
