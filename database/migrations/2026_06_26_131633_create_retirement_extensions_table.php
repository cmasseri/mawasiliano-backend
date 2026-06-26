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
Schema::create('retirement_extensions', function (Blueprint $table) {

    $table->id();

    $table->foreignId('personnel_id')
          ->constrained('personnel')
          ->cascadeOnDelete();

    $table->date('approval_date');

    $table->date('effective_from');

    $table->date('effective_to');

    $table->integer('extension_years');

    $table->string('approval_reference')->nullable();

    $table->text('reason')->nullable();

    $table->text('remarks')->nullable();

    $table->boolean('is_active')->default(true);

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retirement_extensions');
    }
};
