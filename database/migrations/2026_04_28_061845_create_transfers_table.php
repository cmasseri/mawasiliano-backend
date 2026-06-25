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
    Schema::create('transfers', function (Blueprint $table) {

        $table->id();

        $table->foreignId('personnel_id')
            ->constrained('personnel')
            ->cascadeOnDelete();

        $table->foreignId('from_unit_id')
            ->nullable()
            ->constrained('units');

        $table->foreignId('to_unit_id')
            ->constrained('units');

        $table->date('transfer_date');

        $table->string('authority')->nullable();

        $table->text('reason')->nullable();

        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
