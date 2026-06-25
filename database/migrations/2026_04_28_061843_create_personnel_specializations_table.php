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
    Schema::create('personnel_specializations', function (Blueprint $table) {
        $table->foreignId('personnel_id')->constrained()->cascadeOnDelete();
        $table->foreignId('specialization_id')->constrained()->cascadeOnDelete();
        $table->primary(['personnel_id', 'specialization_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_specializations');
    }
};
