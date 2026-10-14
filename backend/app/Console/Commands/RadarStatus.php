<?php

namespace App\Console\Commands;

use App\Models\CompanyDocument;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\SyncRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RadarStatus extends Command
{
    protected $signature = 'radar:status';

    protected $description = 'Exibe o estado do radar e seu histórico.';

    public function handle(): int
    {
        $this->line(json_encode(['database' => config('database.default'), 'opportunities' => Opportunity::count(),
            'consulted_pncp_ids' => DB::table('pncp_consultations')->count(),
            'opportunities_without_consultation_record' => Opportunity::whereNotIn('pncp_id', DB::table('pncp_consultations')->select('pncp_id'))->count(),
            'documents' => Document::count(), 'company_documents' => CompanyDocument::count(),
            'company_records_remaining_in_documents' => Document::whereNull('opportunity_id')->count(),
            'attachments' => DB::table('opportunities')->select('attachments_status')->selectRaw('COUNT(*) AS count')->groupBy('attachments_status')->get(),
            'latest_run' => SyncRun::latest('id')->first()], JSON_UNESCAPED_UNICODE));

        return 0;
    }
}
