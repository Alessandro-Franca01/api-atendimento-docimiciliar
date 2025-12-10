<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['Residencial', 'Trabalho', 'Outro'])->default('Residencial');
            $table->string('street');
            $table->string('number');
            $table->string('complement')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 10);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
