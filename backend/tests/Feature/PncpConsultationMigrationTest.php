<?php
namespace Tests\Feature;

use App\Models\Opportunity;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PncpConsultationMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_consultation_migration_backfills_existing_ids_without_changing_opportunities(): void
    {
        $op = Opportunity::create(['pncp_id'=>'existing-2026-1','cnpj'=>'12345678000100','year'=>2026,'sequence'=>1,
            'object'=>'Projeto arquitetônico','agency'=>'Prefeitura','modality_id'=>4,'modality'=>'Concorrência',
            'scope_reason'=>'Projeto','deadline'=>now()->addDays(10),'source'=>[],'bookmarked'=>true]);
        $before = $op->fresh()->getAttributes();
        Schema::drop('pncp_consultations');
        (require database_path('migrations/2026_10_07_000002_create_pncp_consultations_table.php'))->up();
        $this->assertDatabaseHas('pncp_consultations',['pncp_id'=>$op->pncp_id]);
        $this->assertSame($before,$op->fresh()->getAttributes());
    }
}
