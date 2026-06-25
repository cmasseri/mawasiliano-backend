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
Schema::create('units', function (Blueprint $table) {
    $table->id();
    $table->string('name');
$table->string('gender');
    $table->enum('type', [
        'MMJ',
        'COMMAND',
        'BRIGADE',
        'UNIT',
        'RESERVE_REGION',
        'RESERVE_DISTRICT'
    ]);

    $table->foreignId('parent_id')
        ->nullable()
        ->constrained('units')
        ->cascadeOnDelete();

    $table->string('nickname')->nullable()->unique();
    $table->boolean('is_active')->default(true);

    $table->timestamps();
    $table->softDeletes();

    $table->index('parent_id');
    $table->index('type');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
