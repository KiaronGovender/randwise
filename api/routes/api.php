<?php

use App\Http\Controllers\Api\DemoInsightController;
use App\Http\Controllers\Api\StatementPreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/demo', DemoInsightController::class);
Route::post('/statements/preview', StatementPreviewController::class);
