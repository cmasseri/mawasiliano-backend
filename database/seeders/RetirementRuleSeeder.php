<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RetirementRuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('retirement_rules')->insert([

            ['rank_id' => 1, 'retirement_age' => 54], // Pte
            ['rank_id' => 2, 'retirement_age' => 54], // L/Cpl
            ['rank_id' => 3, 'retirement_age' => 54], // Cpl
            ['rank_id' => 4, 'retirement_age' => 54], // Sgt
            ['rank_id' => 5, 'retirement_age' => 54], // Ssgt

            ['rank_id' => 6, 'retirement_age' => 56], // WOII
            ['rank_id' => 7, 'retirement_age' => 58], // WOI

            ['rank_id' => 8, 'retirement_age' => 50], // 2Lt
            ['rank_id' => 9, 'retirement_age' => 50], // Lt
            ['rank_id' => 10, 'retirement_age' => 52], // Capt
            ['rank_id' => 11, 'retirement_age' => 54], // Major
            ['rank_id' => 12, 'retirement_age' => 56], // Lt Col
            ['rank_id' => 13, 'retirement_age' => 58], // Col

            ['rank_id' => 14, 'retirement_age' => 60], // Brig Gen
            ['rank_id' => 15, 'retirement_age' => 60], // Maj Gen
            ['rank_id' => 16, 'retirement_age' => 60], // Lt Gen
            ['rank_id' => 17, 'retirement_age' => 60], // Gen

        ]);
    }
}