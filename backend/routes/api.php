<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigestController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\SearchProfileController;
use App\Http\Controllers\TenderAttachmentController;
use Illuminate\Support\Facades\Route;

Route::group([], function () {
    Route::get('auth/status', [AuthController::class, 'status']);
    Route::post('setup', [AuthController::class, 'setup'])->middleware('throttle:auth-setup');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('opportunities', [OpportunityController::class, 'opportunities']);
        Route::post('opportunities/export', [OpportunityController::class, 'exportOpportunities'])->middleware('throttle:10,1');
        Route::get('opportunities/{opportunity}', [OpportunityController::class, 'opportunity']);
        Route::patch('opportunities/{opportunity}/discard', [OpportunityController::class, 'discard']);
        Route::post('opportunities/{opportunity}/attachments', [TenderAttachmentController::class, 'store'])->middleware('throttle:tender-attachments');
        Route::patch('opportunities/{opportunity}', [OpportunityController::class, 'bookmark']);
        Route::post('opportunities/{opportunity}/requirements', [RequirementController::class, 'storeRequirement']);
        Route::patch('requirements/{requirement}', [RequirementController::class, 'review']);
        Route::get('profiles', [SearchProfileController::class, 'profiles']);
        Route::post('profiles', [SearchProfileController::class, 'storeProfile']);
        Route::put('profiles/{profile}', [SearchProfileController::class, 'storeProfile']);
        Route::delete('profiles/{profile}', [SearchProfileController::class, 'deleteProfile']);
        Route::get('documents', [DocumentController::class, 'documents']);
        Route::post('documents', [DocumentController::class, 'upload'])->middleware('throttle:20,1');
        Route::get('documents/{document}', [DocumentController::class, 'document']);
        Route::get('documents/{document}/download', [DocumentController::class, 'download']);
        Route::get('tender-documents/{document}', [DocumentController::class, 'tenderDocument']);
        Route::delete('tender-documents/{document}', [DocumentController::class, 'deleteTenderDocument']);
        Route::get('tender-documents/{document}/download', [DocumentController::class, 'tenderDownload']);
        Route::post('digest', [DigestController::class, 'store'])->middleware('throttle:manual-digest');
        Route::post('sync', [ConsultationController::class, 'store'])->middleware('throttle:pncp-consultation');
    });
});
