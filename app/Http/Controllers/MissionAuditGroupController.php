<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionAuditGroup;
use App\Models\QuestionnaireDocumentImport;
use App\Models\QuestionnaireTemplate;
use App\Services\Questionnaires\WordQuestionnaireExtractionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MissionAuditGroupController extends Controller
{
    public function store(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('assignTeamMembers', $mission);
        $data = $this->validateGroup($request, $mission);

        DB::transaction(function () use ($data, $mission, $request): void {
            $group = MissionAuditGroup::query()->create([
                ...collect($data)->except('member_ids')->all(),
                'mission_id' => $mission->id,
                'created_by' => $request->user()->id,
            ]);
            $group->members()->sync($data['member_ids']);
        });

        return back()->with('status', 'Groupe d’audit créé et questionnaire attribué.');
    }

    public function update(Request $request, Mission $mission, MissionAuditGroup $auditGroup): RedirectResponse
    {
        $this->authorizeGroup($request, $mission, $auditGroup);
        $data = $this->validateGroup($request, $mission);

        DB::transaction(function () use ($data, $auditGroup): void {
            $auditGroup->update(collect($data)->except('member_ids')->all());
            $auditGroup->members()->sync($data['member_ids']);
        });

        return back()->with('status', 'Groupe d’audit actualisé.');
    }

    public function destroy(Request $request, Mission $mission, MissionAuditGroup $auditGroup): RedirectResponse
    {
        $this->authorizeGroup($request, $mission, $auditGroup);
        $auditGroup->loadMissing('imports');
        foreach ($auditGroup->imports as $documentImport) {
            Storage::disk('local')->delete($documentImport->stored_path);
        }
        $auditGroup->delete();

        return back()->with('status', 'Groupe d’audit supprimé.');
    }

    public function importQuestionnaire(
        Request $request,
        Mission $mission,
        MissionAuditGroup $auditGroup,
        WordQuestionnaireExtractionService $extractor,
    ): RedirectResponse {
        $this->authorizeGroup($request, $mission, $auditGroup);
        $request->validate([
            'questionnaire_document' => ['required', 'file', 'mimes:docx', 'max:20480'],
        ], [
            'questionnaire_document.mimes' => 'Le questionnaire doit être au format .docx. Convertissez d’abord les anciens fichiers .doc.',
        ]);

        $file = $request->file('questionnaire_document');
        $hash = hash_file('sha256', $file->getRealPath());
        if ($auditGroup->imports()->where('sha256', $hash)->exists()) {
            return back()->withErrors(['questionnaire_document' => 'Ce document a déjà été importé dans ce groupe.']);
        }

        $path = $file->store('questionnaire-imports/'.$mission->id, 'local');
        try {
            $extracted = $extractor->extract(Storage::disk('local')->path($path));
            $suggestions = $extractor->suggest($extracted);
            QuestionnaireDocumentImport::query()->create([
                'mission_audit_group_id' => $auditGroup->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'sha256' => $hash,
                'status' => 'parsed',
                'extracted_data' => $extracted,
                'analysis_suggestions' => $suggestions,
                'uploaded_by' => $request->user()->id,
            ]);
        } catch (\InvalidArgumentException $exception) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['questionnaire_document' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('status', 'Questionnaire importé et pré-analysé. Les propositions restent à valider humainement.');
    }

    /** @return array<string, mixed> */
    private function validateGroup(Request $request, Mission $mission): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'questionnaire_template_id' => ['required', Rule::exists('questionnaire_templates', 'id')->where('active', true)],
            'service_id' => ['nullable', Rule::exists('services', 'id')->where('mission_id', $mission->id)],
            'interviewed_person' => ['nullable', 'string', 'max:255'],
            'interviewed_role' => ['nullable', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['planned', 'in_progress', 'completed'])],
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => [Rule::exists('mission_team_members', 'id')->where('mission_id', $mission->id)],
        ]);

        $questionnaire = QuestionnaireTemplate::query()->findOrFail($data['questionnaire_template_id']);
        if (! $questionnaire->isAvailableForMission($mission)) {
            abort(403, 'Ce questionnaire n’est pas accessible au département de la mission.');
        }

        return $data;
    }

    private function authorizeGroup(Request $request, Mission $mission, MissionAuditGroup $group): void
    {
        $this->authorize('assignTeamMembers', $mission);
        abort_unless((int) $group->mission_id === (int) $mission->id, 404);
    }
}
