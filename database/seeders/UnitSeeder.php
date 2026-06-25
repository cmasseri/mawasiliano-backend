<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
use App\Models\Structure;

public function run(): void
{
    Structure::create([
        'name' => 'Makao Makuu ya Jeshi',
        'type' => 'MMJ',
        'nickname' => 'MMJ',
        'parent_id' => null
    ]);
}
}
