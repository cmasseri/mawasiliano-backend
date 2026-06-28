<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retirement_extensions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('personnel_id')
                ->constrained('personnel')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('years_extended');

            $table->date('approval_date');

            $table->string('approved_by');

            $table->string('reference_number')->nullable();

            $table->text('reason')->nullable();

            $table->text('remarks')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retirement_extensions');
    }
};