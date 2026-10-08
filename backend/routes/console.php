<?php

use App\Models\CompanyDocument;
use App\Models\Document;
use App\Models\Opportunity;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('radar:status', function () {
    $this->line(json_encode(['database' => config('database.default'), 'opportunities' => Opportunity::count(),
        'consulted_pncp_ids' => DB::table('pncp_consultations')->count(),
        'opportunities_without_consultation_record' => Opportunity::whereNotIn('pncp_id', DB::table('pncp_consultations')->select('pncp_id'))->count(),
        'documents' => Document::count(), 'company_documents' => CompanyDocument::count(),
        'company_records_remaining_in_documents' => Document::whereNull('opportunity_id')->count(),
        'attachments' => DB::table('opportunities')->select('attachments_status')->selectRaw('COUNT(*) AS count')->groupBy('attachments_status')->get(),
        'latest_run' => SyncRun::latest('id')->first()], JSON_UNESCAPED_UNICODE));
});
Artisan::command('radar:user {email} {--name=Administrador}', function () {
    $email = $this->argument('email');
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('E-mail inválido.');

        return 1;
    }
    if (User::where('email', $email)->exists()) {
        $this->error('Usuário já existe.');

        return 1;
    }
    $password = $this->secret('Senha (mínimo 12 caracteres)');
    if (strlen($password ?? '') < 12) {
        $this->error('Senha curta.');

        return 1;
    }
    User::create(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
    $this->info('Usuário criado.');
});
