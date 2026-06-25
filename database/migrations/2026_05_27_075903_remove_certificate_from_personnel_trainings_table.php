<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_trainings', function (Blueprint $table) {

            $table->dropColumn('certificate');

        });
    }

    public function down(): void
    {
        Schema::table('personnel_trainings', function (Blueprint $table) {

            $table->string('certificate')->nullable();

        });
    }
};