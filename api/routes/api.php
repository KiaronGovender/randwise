<?php

use App\Http\Controllers\Api\DemoInsightController;
use App\Http\Controllers\Api\StatementImportController;
use App\Http\Controllers\Api\StatementPreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/demo', DemoInsightController::class);
Route::post('/statements/preview', StatementPreviewController::class);
Route::get('/imports', [StatementImportController::class, 'index']);
Route::post('/imports', [StatementImportController::class, 'store']);
Route::get('/imports/{import}', [StatementImportController::class, 'show']);
