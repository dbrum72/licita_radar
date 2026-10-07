<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\SearchProfile;
class RadarSeeder extends Seeder
{
    public function run(): void
    {
        SearchProfile::firstOrCreate(['name'=>'Projetos e anteprojetos — Brasil'],[
            'keywords'=>['projeto','anteprojeto','arquitetura'],'states'=>[],'modalities'=>[],
            'min_days'=>3,'active'=>true,
        ]);
    }
}
