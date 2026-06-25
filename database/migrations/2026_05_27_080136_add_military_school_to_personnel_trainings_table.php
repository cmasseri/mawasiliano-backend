<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_trainings', function (Blueprint $table) {

            $table->string('military_school')->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('personnel_trainings', function (Blueprint $table) {

            $table->dropColumn('military_school');

        });
    }
};