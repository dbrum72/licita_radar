<?php

namespace Database\Seeders;

use App\Models\SearchProfile;
use Illuminate\Database\Seeder;

class RadarSeeder extends Seeder
{
    public function run(): void
    {
        SearchProfile::firstOrCreate(['name' => 'Projetos e anteprojetos — Brasil'], [
            'keywords' => ['projeto', 'anteprojeto', 'arquitetura'], 'states' => [], 'modalities' => [],
            'min_days' => 3, 'active' => true,
        ]);
    }
}
