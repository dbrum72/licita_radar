<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RadarController as Radar;
use App\Jobs\SyncPncp;

Route::prefix('api')->group(function () {
    Route::get('csrf',[Radar::class,'csrf']);
    Route::post('setup',[Radar::class,'setup'])->middleware('throttle:5,1');
    Route::post('login',[Radar::class,'login'])->middleware('throttle:5,1');
    Route::middleware('auth')->group(function () {
        Route::get('me',fn (\Illuminate\Http\Request $r) => $r->user());
        Route::post('logout',[Radar::class,'logout']);
        Route::get('dashboard',[Radar::class,'dashboard']);
        Route::get('opportunities',[Radar::class,'opportunities']);
        Route::post('opportunities/export',[Radar::class,'exportOpportunities'])->middleware('throttle:10,1');
        Route::get('opportunities/{opportunity}',[Radar::class,'opportunity']);
        Route::patch('opportunities/{opportunity}/discard',[Radar::class,'discard']);
        Route::post('opportunities/{opportunity}/attachments',function (\App\Models\Opportunity $opportunity) {
            if (!$opportunity->deadline || !$opportunity->deadline->gt(now()) || $opportunity->dismissal()->exists() || app(\App\Services\CompanyScopeService::class)->assess($opportunity->object,$opportunity->deadline)['status'] !== 'compatible') {
                return response()->json(['message'=>'Compatibilidade com o acervo atual não demonstrada. Cadastre documentação válida antes de buscar anexos.'],422);
            }
            \App\Jobs\FetchTenderDocuments::dispatch($opportunity->id);
            return response()->json(['message'=>'Busca de anexos enviada à fila.'],202);
        })->middleware('throttle:2,5');
        Route::patch('opportunities/{opportunity}',[Radar::class,'bookmark']);
        Route::post('opportunities/{opportunity}/requirements',[Radar::class,'storeRequirement']);
        Route::patch('requirements/{requirement}',[Radar::class,'review']);
        Route::get('profiles',[Radar::class,'profiles']);
        Route::post('profiles',[Radar::class,'storeProfile']);
        Route::put('profiles/{profile}',[Radar::class,'storeProfile']);
        Route::delete('profiles/{profile}',[Radar::class,'deleteProfile']);
        Route::get('documents',[Radar::class,'documents']);
        Route::post('documents',[Radar::class,'upload'])->middleware('throttle:20,1');
        Route::get('documents/{document}',[Radar::class,'document']);
        Route::get('documents/{document}/download',[Radar::class,'download']);
        Route::get('tender-documents/{document}',[Radar::class,'tenderDocument']);
        Route::get('tender-documents/{document}/download',[Radar::class,'tenderDownload']);
        Route::post('sync',function () { SyncPncp::dispatch(); return response()->json(['message'=>'Consulta enviada à fila.'],202); })->middleware('throttle:1,5');
    });
});
Route::get('/{path?}',function () {
    $path = public_path('app/index.html');
    if (!file_exists($path)) return response('Execute npm run build na pasta frontend para abrir o LicitaRadar.',503);
    return response()->file($path);
})->where('path','^(?!api(?:/|$)).*');
