<?php

namespace App\Http\Controllers\Dgcpt;

use App\Http\Controllers\Controller;
use App\Services\Dgcpt\QuestionnaireImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionnaireImportController extends Controller
{
    public function __construct(
        private QuestionnaireImportService $imports,
    ) {}

    public function index(): View
    {
        return view('dgcpt.questionnaire-import.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:docx,xlsx,xls', 'max:20480'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $template = $this->imports->importToQuestionnaireTemplate(
            $validated['file'],
            [
                'name' => $validated['name'] ?? null,
                'created_by' => $request->user()?->id,
            ],
        );

        $detection = $this->imports->detectContextFromFilename($validated['file']->getClientOriginalName());

        return redirect()
            ->route('questionnaire-builder.edit', $template)
            ->with('status', 'Questionnaire importé en brouillon. Contexte détecté : '
                .($detection['suggested_entity']['name'] ?? 'entité à préciser'));
    }

    public function preview(Request $request): View
    {
        $request->validate([
            'filename' => ['required', 'string', 'max:255'],
        ]);

        return view('dgcpt.questionnaire-import.preview', [
            'detection' => $this->imports->detectContextFromFilename($request->input('filename')),
        ]);
    }
}
