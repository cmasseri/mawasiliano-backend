<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('promotions', function (Blueprint $table) {

            // ADD ONLY IF NOT EXISTS
            if (!Schema::hasColumn('promotions', 'date_promoted')) {
                $table->date('date_promoted')->nullable();
            }

            if (!Schema::hasColumn('promotions', 'is_current')) {
                $table->boolean('is_current')->default(true);
            }

            if (!Schema::hasColumn('promotions', 'personnel_id')) {
                $table->unsignedBigInteger('personnel_id');
            }

            if (!Schema::hasColumn('promotions', 'rank_id')) {
                $table->unsignedBigInteger('rank_id');
            }
        });
    }

    public function down()
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (Schema::hasColumn('promotions', 'is_current')) {
                $table->dropColumn('is_current');
            }
        });
    }
};