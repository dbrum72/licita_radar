<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigestController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\OpportunityController;
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
        Route::get('opportunities', [OpportunityController::class, 'index']);
        Route::post('opportunities/export', [OpportunityController::class, 'export'])->middleware('throttle:10,1');
        Route::get('opportunities/{opportunity}', [OpportunityController::class, 'show']);
        Route::patch('opportunities/{opportunity}/discard', [OpportunityController::class, 'discard']);
        Route::post('opportunities/{opportunity}/attachments', [TenderAttachmentController::class, 'store'])->middleware('throttle:tender-attachments');
        Route::patch('opportunities/{opportunity}', [OpportunityController::class, 'bookmark']);
        Route::get('profiles', [SearchProfileController::class, 'index']);
        Route::post('profiles', [SearchProfileController::class, 'store']);
        Route::put('profiles/{profile}', [SearchProfileController::class, 'update']);
        Route::delete('profiles/{profile}', [SearchProfileController::class, 'destroy']);
        Route::get('documents', [DocumentController::class, 'index']);
        Route::post('documents', [DocumentController::class, 'store'])->middleware('throttle:20,1');
        Route::get('documents/{document}/download', [DocumentController::class, 'download']);
        Route::delete('tender-documents/{document}', [DocumentController::class, 'deleteTenderDocument']);
        Route::get('tender-documents/{document}/download', [DocumentController::class, 'tenderDownload']);
        Route::post('digest', [DigestController::class, 'store'])->middleware('throttle:manual-digest');
        Route::post('sync', [ConsultationController::class, 'store'])->middleware('throttle:pncp-consultation');
    });
});
