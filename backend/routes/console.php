<?php

use Illuminate\Support\Facades\{Artisan, Schedule};
use App\Services\{SyncService, DigestService};
use App\Models\User;

Artisan::command('radar:sync', function (SyncService $sync, DigestService $digest) {
    $run = $sync->run(); $this->info("Consulta {$run->id}: {$run->status}, {$run->imported} novas oportunidades.");
    if ($run->status !== 'completed') return 1;
    $digest->send(); return 0;
})->purpose('Consulta PNCP e envia alertas de novos projetos');
Artisan::command('radar:digest',function (DigestService $service) { $this->info($service->send().' e-mails enviados.'); });
Artisan::command('radar:prune',function (\App\Services\OpportunityLifecycleService $service) {
    $this->info($service->pruneExpiredDiscarded().' oportunidades descartadas vencidas excluídas.');
})->purpose('Exclui descartadas vencidas e preserva os identificadores PNCP consultados');
Artisan::command('radar:mail-test',function () {
    if (!app()->environment('local') || config('mail.default')!=='smtp' || !in_array(config('mail.mailers.smtp.host'),['127.0.0.1','localhost']) || (int)config('mail.mailers.smtp.port')!==1025) {
        $this->error('Este teste é permitido apenas no Mailpit local, porta 1025.'); return 1;
    }
    \Illuminate\Support\Facades\Mail::raw('Captura local de e-mail do LicitaRadar via Mailpit. Nenhuma oportunidade real está associada a este teste.',
        fn ($mail) => $mail->to('desenvolvimento@licitaradar.test')->subject('LicitaRadar — teste de integração Mailpit'));
    $this->info('Mensagem de teste enviada ao Mailpit local.');
});
Artisan::command('radar:attachments',function () {
    $count = 0;
    $ids = app(\App\Services\CompanyScopeService::class)->compatibleIds();
    \App\Models\Opportunity::where('scope_status','relevant')->where('deadline','>',now())
        ->whereIn('id',$ids)
        ->whereIn('attachments_status',['pending','failed'])->each(function ($op) use (&$count) {
            \App\Jobs\FetchTenderDocuments::dispatch($op->id); $count++;
        });
    $this->info("{$count} oportunidades encaminhadas à fila de anexos.");
});
Artisan::command('radar:status',function () {
    $this->line(json_encode(['database'=>config('database.default'),'opportunities'=>\App\Models\Opportunity::count(),
        'consulted_pncp_ids'=>\Illuminate\Support\Facades\DB::table('pncp_consultations')->count(),
        'opportunities_without_consultation_record'=>\App\Models\Opportunity::whereNotIn('pncp_id',\Illuminate\Support\Facades\DB::table('pncp_consultations')->select('pncp_id'))->count(),
        'documents'=>\App\Models\Document::count(),'company_documents'=>\App\Models\CompanyDocument::count(),
        'company_records_remaining_in_documents'=>\App\Models\Document::whereNull('opportunity_id')->count(),
        'queued_jobs'=>\Illuminate\Support\Facades\DB::table('jobs')->count(),
        'attachments'=>\Illuminate\Support\Facades\DB::table('opportunities')->select('attachments_status')->selectRaw('COUNT(*) AS count')->groupBy('attachments_status')->get(),
        'latest_run'=>\App\Models\SyncRun::latest('id')->first()],JSON_UNESCAPED_UNICODE));
});
Artisan::command('radar:user {email} {--name=Administrador}',function () {
    $email = $this->argument('email');
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $this->error('E-mail inválido.'); return 1; }
    if (User::where('email',$email)->exists()) { $this->error('Usuário já existe.'); return 1; }
    $password = $this->secret('Senha (mínimo 12 caracteres)');
    if (strlen($password ?? '')<12) { $this->error('Senha curta.'); return 1; }
    User::create(['name'=>$this->option('name'),'email'=>$email,'password'=>$password]); $this->info('Usuário criado.');
});
Schedule::command('radar:sync')->dailyAt(config('radar.sync_time'))->timezone('America/Sao_Paulo')->withoutOverlapping(360);
Schedule::command('radar:prune')->everyMinute()->withoutOverlapping();
