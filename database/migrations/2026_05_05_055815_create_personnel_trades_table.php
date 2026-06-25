<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    if (!Schema::hasTable('personnel_trades')) {

        Schema::create('personnel_trades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('personnel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trade_id')->constrained()->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->boolean('is_current')->default(true);

            $table->timestamps();

            $table->index(['personnel_id', 'is_current']);
        });

    }
}

    public function down(): void
    {
        Schema::dropIfExists('personnel_trades');
    }
};