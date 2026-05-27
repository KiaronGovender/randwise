<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatementAnalyzer;
use Illuminate\Http\JsonResponse;

class DemoInsightController extends Controller
{
    public function __invoke(StatementAnalyzer $analyzer): JsonResponse
    {
        return response()->json($analyzer->demo());
    }
}
