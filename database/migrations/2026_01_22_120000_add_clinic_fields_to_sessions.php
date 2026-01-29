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
        Schema::table('sessions', function (Blueprint $table) {
            $table->enum('category', ['private', 'clinic'])->default('private')->after('status');
            // $table->enum('room', ['no_room', 'room1', 'room2', 'room3', 'room4'])->default('no_room')->after('category');
            $table->foreignId('health_plan_id')->nullable()->after('category')->constrained('health_plans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['health_plan_id']);
            $table->dropColumn('health_plan_id');
            $table->dropColumn('category');
            $table->dropColumn('room');
        });
    }
};
