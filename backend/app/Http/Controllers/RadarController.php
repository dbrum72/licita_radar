<?php
namespace App\Http\Controllers;

use App\Models\{Opportunity, SearchProfile, Document, CompanyDocument, Requirement, SyncRun};
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Storage};
use Illuminate\Validation\ValidationException;

class RadarController extends Controller
{
    public function csrf() { return response()->json(['token'=>csrf_token(),'setup'=>!\App\Models\User::exists()]); }
    public function setup(Request $r)
    {
        if (\App\Models\User::exists()) abort(403, 'A configuração inicial já foi concluída.');
        // Initial access is deliberately restricted to the local machine.
        if (!in_array($r->ip(), ['127.0.0.1','::1'])) abort(403, 'Crie o primeiro usuário pelo comando radar:user no servidor.');
        $data = $r->validate(['name'=>'required|string|max:120','email'=>'required|email|max:255|unique:users','password'=>'required|string|min:12|max:128']);
        return \Illuminate\Support\Facades\Cache::lock('radar-setup',30)->block(2,function () use ($data,$r) {
            if (\App\Models\User::exists()) abort(403);
            $user = \App\Models\User::create($data); Auth::login($user); $r->session()->regenerate();
            return response()->json(['user'=>$user,'token'=>csrf_token()],201);
        });
    }
    public function login(Request $r)
    {
        $data = $r->validate(['email'=>'required|email','password'=>'required|string']);
        if (!Auth::attempt($data)) throw ValidationException::withMessages(['email'=>'E-mail ou senha incorretos.']);
        $r->session()->regenerate();
        return response()->json(['user'=>$r->user(),'token'=>csrf_token()]);
    }
    public function logout(Request $r)
    {
        Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken();
        return response()->json(['token'=>csrf_token()]);
    }
    public function dashboard()
    {
        app(\App\Services\OpportunityLifecycleService::class)->pruneExpiredDiscarded();
        $ids = app(\App\Services\CompanyScopeService::class)->compatibleIds();
        return ['total'=>count($ids),'open'=>Opportunity::whereIn('id',$ids)->where('deadline','>',now())->count(),
            'relevant'=>Opportunity::whereIn('id',$ids)->where('deadline','>',now())->count(),
            'pending'=>Requirement::where('status','pending')->count(),'documents'=>CompanyDocument::count(),
            'runs'=>SyncRun::latest()->limit(8)->get(),
            'expiring'=>CompanyDocument::whereNotNull('expires_at')->where('expires_at','<=',today()->addDays(30))->get(['id','name','expires_at']),
            'mail_configured'=>!in_array(config('mail.default'),['log','array'])];
    }
    public function opportunities(Request $r)
    {
        app(\App\Services\OpportunityLifecycleService::class)->pruneExpiredDiscarded();
        $company = app(\App\Services\CompanyScopeService::class);
        $q = Opportunity::where('deadline','>',now())->withExists('dismissal as discarded');
        if ($r->boolean('discarded')) $q->whereHas('dismissal');
        else $q->whereIn('id',$company->compatibleIds());
        if ($r->filled('keyword')) $q->where('object','like','%'.$r->string('keyword').'%');
        if ($r->filled('state')) $q->where('state',$r->string('state'));
        if ($r->filled('agency')) $q->where('agency','like','%'.$r->string('agency').'%');
        if ($r->filled('modality')) $q->where('modality_id',$r->integer('modality'));
        if ($r->filled('scope')) $q->where('scope_status',$r->string('scope'));
        if ($r->filled('min_value')) $q->where('estimated_value','>=',$r->float('min_value'));
        if ($r->filled('max_value')) $q->where('estimated_value','<=',$r->float('max_value'));
        if ($r->filled('deadline')) { $r->validate(['deadline'=>'date']); $q->whereDate('deadline','<=',$r->input('deadline')); }
        if ($r->boolean('open')) $q->where('deadline','>',now());
        if ($r->boolean('bookmarked')) $q->where('bookmarked',true);
        $results = $q->orderBy('deadline')->paginate(20);
        $results->getCollection()->each(fn ($item) => $item->setAttribute('company_match',$company->assess($item->object,$item->deadline)));
        return response()->json([...$results->toArray(),'company_notice'=>$company->notice()]);
    }
    public function opportunity(Opportunity $opportunity, DocumentService $service)
    {
        app(\App\Services\OpportunityLifecycleService::class)->pruneExpiredDiscarded();
        abort_unless(Opportunity::whereKey($opportunity->id)->exists(),404);
        $opportunity->load('documents','requirements.evidence');
        $opportunity->setAttribute('discarded',$opportunity->dismissal()->exists());
        $companyMatch = app(\App\Services\CompanyScopeService::class)->assess($opportunity->object,$opportunity->deadline);
        $opportunity->setAttribute('company_match',$companyMatch);
        $requirements = $opportunity->requirements;
        $incomplete = $opportunity->documents->isEmpty() || in_array($opportunity->attachments_status,['failed','partial','running']) || $opportunity->documents->contains(fn ($d) => $d->status !== 'ready');
        $invalidEvidence = $requirements->contains(function ($req) use ($opportunity) {
            if ($req->status !== 'met') return false;
            $doc = $req->evidence; $date = $opportunity->deadline ?? now();
            return !$doc || ($doc->expires_at && $doc->expires_at->endOfDay()->lt($date));
        });
        $verdict = $opportunity->scope_status === 'excluded' ? 'Fora do escopo inicial' :
            (($opportunity->deadline && $opportunity->deadline->isPast()) ? 'Prazo encerrado' :
            ($requirements->contains('status','unmet') ? 'Há requisitos não atendidos' :
            (($incomplete || $invalidEvidence || $requirements->isEmpty() || $requirements->contains('status','pending')) ? 'Análise documental pendente' : 'Requisitos cadastrados revisados')));
        foreach ($requirements as $req) $req->setAttribute('candidates',$service->candidates($req));
        if ($companyMatch['status'] !== 'compatible') $verdict = 'Compatibilidade com o acervo pendente ou não demonstrada';
        return ['opportunity'=>$opportunity,'verdict'=>$verdict,'notice'=>'A extração é uma triagem e pode omitir exigências. Revise integralmente o edital e cadastre requisitos faltantes. Análise econômica ainda não implementada.'];
    }
    public function bookmark(Opportunity $opportunity, Request $r)
    { $data = $r->validate(['bookmarked'=>'required|boolean']); $opportunity->update($data); return $opportunity; }
    public function discard(Opportunity $opportunity, Request $r)
    {
        $data = $r->validate(['discarded'=>'required|boolean']);
        $restorable = \Illuminate\Support\Facades\DB::transaction(function () use ($opportunity,$r,$data) {
            $current = Opportunity::lockForUpdate()->find($opportunity->id);
            if (!$current) return false;
            if (!$data['discarded'] && (!$current->deadline || !$current->deadline->gt(now()))) return false;
            if ($data['discarded']) $current->dismissal()->firstOrCreate([],['user_id'=>$r->user()->id]);
            else $current->dismissal()->delete();
            return true;
        });
        app(\App\Services\OpportunityLifecycleService::class)->pruneExpiredDiscarded();
        if (!$restorable) throw ValidationException::withMessages(['discarded'=>'Restauração permitida somente antes do vencimento. Descartadas vencidas são excluídas.']);
        return ['id'=>$opportunity->id,'discarded'=>$data['discarded']];
    }
    public function exportOpportunities(Request $r, \App\Services\OpportunityPdfService $pdf)
    {
        $data = $r->validate(['ids'=>'required|array|min:1|max:50','ids.*'=>'required|integer|distinct|exists:opportunities,id']);
        $company = app(\App\Services\CompanyScopeService::class);
        $items = Opportunity::whereIn('id',$data['ids'])->whereDoesntHave('dismissal')->get()->keyBy('id');
        $ordered = collect($data['ids'])->map(function ($id) use ($items,$company) {
            $item = $items->get($id);
            if (!$item) throw ValidationException::withMessages(['ids'=>'Há oportunidades descartadas na seleção. Atualize a lista.']);
            if (!$item->deadline || !$item->deadline->gt(now())) throw ValidationException::withMessages(['ids'=>'Há oportunidades com prazo encerrado ou desconhecido na seleção. Atualize a lista.']);
            $match = $company->assess($item->object,$item->deadline);
            if ($match['status'] !== 'compatible') throw ValidationException::withMessages(['ids'=>'A compatibilidade documental de uma oportunidade selecionada mudou. Atualize a lista.']);
            $item->setAttribute('company_match',$match);
            return $item;
        });
        return response($pdf->render($ordered),200,[
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'attachment; filename="licitaradar-oportunidades.pdf"',
            'Cache-Control'=>'private, no-store',
            'X-Content-Type-Options'=>'nosniff',
        ]);
    }
    public function profiles() { return SearchProfile::latest()->get(); }
    public function storeProfile(Request $r, ?SearchProfile $profile = null)
    {
        $data = $r->validate(['name'=>'required|string|max:120','keywords'=>'sometimes|array','keywords.*'=>'required|string|max:120',
            'modalities'=>'nullable|array','modalities.*'=>'integer|between:1,13',
            'states'=>'nullable|array','states.*'=>'string|in:AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO',
            'agency'=>'nullable|string|max:255','min_value'=>'nullable|numeric|min:0','max_value'=>'nullable|numeric|min:0',
            'min_days'=>'required|integer|between:0,365','email'=>'nullable|email|max:255','active'=>'required|boolean']);
        $data['keywords'] = [];
        if (isset($data['min_value'],$data['max_value']) && $data['max_value']<$data['min_value']) throw ValidationException::withMessages(['max_value'=>'Valor máximo deve ser maior ou igual ao mínimo.']);
        if ($profile && $profile->exists) { $profile->update($data); return $profile; }
        return response()->json(SearchProfile::create($data),201);
    }
    public function deleteProfile(SearchProfile $profile) { $profile->delete(); return response()->noContent(); }
    public function documents() { return CompanyDocument::latest()->get(); }
    public function upload(Request $r, DocumentService $service)
    {
        $data = $r->validate(['file'=>'required|file|mimes:pdf|max:20480','kind'=>'required|in:certificate,attestation,rrt,cnpj,tender',
            'expires_at'=>'nullable|date','opportunity_id'=>'nullable|integer|exists:opportunities,id']);
        if (($data['kind']==='tender') !== !empty($data['opportunity_id'])) throw ValidationException::withMessages(['kind'=>'Edital deve estar vinculado à oportunidade; documentos da empresa não devem.']);
        $file = $r->file('file');
        $attributes = ['name'=>$file->getClientOriginalName(),'kind'=>$data['kind'],'expires_at'=>$data['expires_at'] ?? null,
            'path'=>$file->store($data['kind']==='tender'?'documents':'company-documents','local'),'sha256'=>hash_file('sha256',$file->getRealPath())];
        $document = $data['kind']==='tender' ? Document::create([...$attributes,'opportunity_id'=>$data['opportunity_id']]) : CompanyDocument::create($attributes);
        $service->extract($document); return response()->json($document->fresh(),201);
    }
    public function download(CompanyDocument $document) { return Storage::disk('local')->download($document->getRawOriginal('path'),$document->name); }
    public function document(CompanyDocument $document) { return $document; }
    public function tenderDocument(Document $document) { return $document; }
    public function tenderDownload(Document $document) { return Storage::disk('local')->download($document->getRawOriginal('path'),$document->name); }
    public function storeRequirement(Opportunity $opportunity, Request $r)
    {
        $data = $r->validate(['source_document_id'=>'required|integer|exists:documents,id','source_page'=>'required|integer|min:1','description'=>'required|string|max:4000']);
        $doc = Document::findOrFail($data['source_document_id']);
        if ($doc->opportunity_id !== $opportunity->id || !collect($doc->pages)->contains('number',$data['source_page'])) abort(422,'Documento/página inválidos para esta oportunidade.');
        return response()->json($opportunity->requirements()->create($data),201);
    }
    public function review(Requirement $requirement, Request $r)
    {
        $data = $r->validate(['status'=>'required|in:pending,met,unmet,not_applicable',
            'evidence_document_id'=>'nullable|required_if:status,met|integer|exists:company_documents,id',
            'evidence_page'=>'nullable|required_if:status,met|integer|min:1','notes'=>'required|string|max:4000']);
        if ($data['status']==='met') {
            $doc = CompanyDocument::find($data['evidence_document_id']); $deadline = Opportunity::find($requirement->opportunity_id)->deadline ?? now();
            if (!in_array($doc->status,['ready','needs_ocr']) ||
                ($doc->expires_at && $doc->expires_at->endOfDay()->lt($deadline)) || !collect($doc->pages)->contains('number',$data['evidence_page'])) abort(422,'Evidência inválida, vencida até o prazo do edital ou página inexistente.');
        } else { $data['evidence_document_id']=null; $data['evidence_page']=null; }
        $requirement->update([...$data,'reviewer_id'=>$r->user()->id,'reviewed_at'=>now()]); return $requirement;
    }
}
