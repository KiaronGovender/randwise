<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatementAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatementPreviewController extends Controller
{
    public function __invoke(Request $request, StatementAnalyzer $analyzer): JsonResponse
    {
        $validated = $request->validate([
            'statement' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
            'bank' => ['nullable', 'string', 'max:80'],
        ]);

        return response()->json(
            $analyzer->fromUploadedFile($validated['statement'], $validated['bank'] ?? 'Imported statement')
        );
    }
}
