<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StatementImport;
use App\Services\StatementImportPresenter;
use App\Services\StatementImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatementImportController extends Controller
{
    public function index(StatementImportPresenter $presenter): JsonResponse
    {
        $imports = StatementImport::query()
            ->latest('imported_at')
            ->limit(10)
            ->get()
            ->map(fn (StatementImport $import) => $presenter->summary($import));

        return response()->json([
            'data' => $imports,
        ]);
    }

    public function store(Request $request, StatementImportService $imports): JsonResponse
    {
        $validated = $request->validate([
            'statement' => ['required_without:statementContents', 'file', 'mimes:csv,txt', 'max:4096'],
            'statementContents' => ['required_without:statement', 'string', 'max:512000'],
            'sourceFilename' => ['required_with:statementContents', 'string', 'max:160'],
            'bank' => ['required', 'string', 'max:80'],
        ]);

        $import = isset($validated['statementContents'])
            ? $imports->storeFromContents(
                $validated['statementContents'],
                $validated['sourceFilename'],
                $validated['bank'],
            )
            : $imports->store($validated['statement'], $validated['bank']);

        return response()->json($imports->dashboard($import), 201);
    }

    public function show(StatementImport $import, StatementImportPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->dashboard($import));
    }
}
