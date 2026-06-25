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
    Schema::create('personnel_operations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('personnel_id')->constrained()->cascadeOnDelete();
        $table->foreignId('operation_id')->constrained()->cascadeOnDelete();
        $table->foreignId('role_id')->nullable()->constrained('operation_roles');
        $table->foreignId('unit_id')->nullable()->constrained();
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->enum('status', ['ASSIGNED','DEPLOYED','COMPLETED','INJURED'])->nullable();
        $table->text('remarks')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_operations');
    }
};
