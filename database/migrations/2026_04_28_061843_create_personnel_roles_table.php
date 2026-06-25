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
    Schema::create('personnel_roles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('personnel_id')->constrained()->cascadeOnDelete();
        $table->foreignId('role_id')->constrained();
        $table->foreignId('unit_id')->nullable()->constrained();
        $table->date('start_date');
        $table->date('end_date')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_roles');
    }
};
