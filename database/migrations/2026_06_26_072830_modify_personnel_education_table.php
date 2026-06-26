<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_education', function (Blueprint $table) {

            $table->string('qualification')->after('personnel_id');

            $table->string('field_of_study')->after('qualification');

            $table->dropColumn('name');

        });
    }

    public function down(): void
    {
        Schema::table('personnel_education', function (Blueprint $table) {

            $table->string('name');

            $table->dropColumn([
                'qualification',
                'field_of_study'
            ]);

        });
    }
};