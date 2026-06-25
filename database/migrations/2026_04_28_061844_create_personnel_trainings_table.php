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
    Schema::create('personnel_trainings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('personnel_id')->constrained()->cascadeOnDelete();
        $table->foreignId('training_id')->constrained();
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->string('certificate')->nullable();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_trainings');
    }
};
