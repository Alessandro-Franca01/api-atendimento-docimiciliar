<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained()->onDelete('cascade');
            $table->enum('day_of_week', ['Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado', 'Domingo']);
            $table->time('time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_schedules');
    }
};
