<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Structure;

class StructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
public function run()
{
    Structure::create([
        'name' => 'Makao Makuu ya Jeshi',
        'type' => 'MMJ',
        'parent_id' => null
    ]);
}
}
