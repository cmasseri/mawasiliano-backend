<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('appointments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('personnel_id')->constrained()->cascadeOnDelete();
        $table->foreignId('unit_id')->constrained()->cascadeOnDelete();

        $table->string('title');

        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
