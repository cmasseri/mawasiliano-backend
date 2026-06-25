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
    Schema::create('personnel', function (Blueprint $table) {
        $table->id();
        $table->string('full_name');
        $table->string('service_number')->unique();
        $table->foreignId('rank_id')->constrained()->cascadeOnDelete();
        $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
        $table->string('education_level')->nullable();
        $table->string('date_of_birth')->nullable();
        $table->enum('status', ['ACTIVE','INACTIVE','RETIRED'])->default('ACTIVE');
        $table->date('date_of_enlistment')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
