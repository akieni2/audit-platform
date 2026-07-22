<?php

namespace App\Http\Controllers\Dgcpt;

use App\Http\Controllers\Controller;
use App\Models\QuestionnaireTemplate;
use App\Services\Dgcpt\QuestionnaireImportService;
use App\Services\Questionnaires\QuestionnairePublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionnaireImportController extends Controller
{
    public function __construct(
        private QuestionnaireImportService $imports,
        private QuestionnairePublishingService $publishing,
    ) {}

    public function index(): View
    {
        return view('dgcpt.questionnaire-import.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:docx', 'max:20480'],
            'name' => ['nullable', 'string', 'max:255'],
            'publish_now' => ['nullable', 'boolean'],
        ]);

        try {
            $template = $this->imports->importToQuestionnaireTemplate(
                $validated['file'],
                ['name' => $validated['name'] ?? null, 'created_by' => $request->user()?->id],
            );
            if ($request->boolean('publish_now')) {
                $template = $this->publishing->publish($template, $request->user());
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()])->withInput();
        }

        $detection = $this->imports->detectContextFromFilename($validated['file']->getClientOriginalName());
        $status = $template->active ? 'Questionnaire importé et publié. ' : 'Questionnaire importé en brouillon. ';

        return redirect()
            ->route('questionnaire-builder.edit', $template)
            ->with('status', $status.'Contexte détecté : '.($detection['suggested_entity']['name'] ?? 'entité à préciser'));
    }

    public function preview(Request $request): View
    {
        $request->validate(['filename' => ['required', 'string', 'max:255']]);

        return view('dgcpt.questionnaire-import.preview', [
            'detection' => $this->imports->detectContextFromFilename($request->input('filename')),
        ]);
    }

    public function downloadSource(QuestionnaireTemplate $template): StreamedResponse
    {
        $this->authorize('view', $template);
        abort_if(blank($template->source_document_path), 404);
        abort_unless(Storage::disk('local')->exists($template->source_document_path), 404);

        return Storage::disk('local')->download(
            $template->source_document_path,
            $template->source_document_name ?: 'questionnaire-source.docx',
        );
    }
}
