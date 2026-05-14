<?php

namespace App\Http\Controllers;

use App\Http\Requests\Forms\StoreFormFieldRequest;
use App\Http\Requests\Forms\StoreFormTemplateRequest;
use App\Http\Requests\Forms\UpdateFormFieldRequest;
use App\Http\Requests\Forms\UpdateFormTemplateRequest;
use App\Models\Department;
use App\Models\FormField;
use App\Models\FormTemplate;
use App\Services\Forms\FormPublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class FormBuilderController extends Controller
{
    public function __construct(
        private FormPublishingService $publishing,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', FormTemplate::class);

        return view('forms.builder.index', [
            'templates' => FormTemplate::query()
                ->withCount(['fields', 'submissions'])
                ->with('sourceTemplate')
                ->orderByRaw("CASE lifecycle_status
                    WHEN 'draft' THEN 0
                    WHEN 'published' THEN 1
                    WHEN 'deprecated' THEN 2
                    WHEN 'archived' THEN 3
                    ELSE 4 END")
                ->orderByDesc('updated_at')
                ->paginate(12)
                ->withQueryString(),
            'departmentOptions' => Department::query()->where('active', true)->orderBy('code')->get(),
            'fieldTypeLabels' => FormField::fieldTypeLabels(),
        ]);
    }

    public function create(): View
    {
        return $this->index();
    }

    public function edit(Request $request, FormTemplate $template): View
    {
        $this->authorize('view', $template);

        $template->load([
            'fields.options' => fn ($query) => $query->orderBy('sort_order'),
            'sourceTemplate',
        ]);

        $selectedFieldId = (int) $request->query('field', $template->fields->sortBy('sort_order')->first()?->id);
        $selectedField = $template->fields->firstWhere('id', $selectedFieldId);

        return view('forms.builder.edit', [
            'template' => $template,
            'selectedField' => $selectedField,
            'lineageTemplates' => $this->lineageTemplates($template),
            'departmentOptions' => Department::query()->where('active', true)->orderBy('code')->get(),
            'fieldTypeLabels' => FormField::fieldTypeLabels(),
        ]);
    }

    public function storeTemplate(StoreFormTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', FormTemplate::class);

        $template = DB::transaction(function () use ($request) {
            return FormTemplate::query()->create([
                ...$request->validated(),
                'active' => false,
                'version' => 1,
                'lifecycle_status' => FormTemplate::STATUS_DRAFT,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);
        });

        return redirect()
            ->route('form-builder.edit', $template)
            ->with('status', 'Brouillon de formulaire créé.');
    }

    public function updateTemplate(UpdateFormTemplateRequest $request, FormTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $request->user());

        DB::transaction(function () use ($editableTemplate, $request) {
            $editableTemplate->update([
                ...$request->validated(),
                'active' => $editableTemplate->lifecycle_status === FormTemplate::STATUS_PUBLISHED,
                'updated_by' => $request->user()?->id,
            ]);
        });

        return redirect()
            ->route('form-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis mise à jour.'
                : 'Formulaire mis à jour.');
    }

    public function storeField(StoreFormFieldRequest $request, FormTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $request->user());
        $validated = $request->validated();
        $fieldAttributes = collect($validated)->except('options_payload')->all();

        $field = DB::transaction(function () use ($editableTemplate, $validated, $fieldAttributes) {
            $field = $editableTemplate->fields()->create([
                ...$fieldAttributes,
                'sort_order' => $fieldAttributes['sort_order'] ?? ((int) $editableTemplate->fields()->max('sort_order') + 1),
            ]);

            $this->publishing->syncFieldOptions($field, $validated['options_payload'] ?? []);

            return $field;
        });

        return redirect()
            ->route('form-builder.edit', ['template' => $editableTemplate, 'field' => $field->id])
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis le champ a été ajouté.'
                : 'Champ ajouté.');
    }

    public function updateField(UpdateFormFieldRequest $request, FormField $field): RedirectResponse
    {
        $template = $field->formTemplate;
        abort_unless($template instanceof FormTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableField, $cloned] = $this->editableFieldContext($field, $request->user());
        $validated = $request->validated();
        $fieldAttributes = collect($validated)->except('options_payload')->all();

        DB::transaction(function () use ($editableField, $validated, $fieldAttributes) {
            $editableField->update($fieldAttributes);
            $this->publishing->syncFieldOptions($editableField, $validated['options_payload'] ?? []);
        });

        return redirect()
            ->route('form-builder.edit', ['template' => $editableTemplate, 'field' => $editableField->id])
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis le champ a été mis à jour.'
                : 'Champ mis à jour.');
    }

    public function destroyField(Request $request, FormField $field): RedirectResponse
    {
        $template = $field->formTemplate;
        abort_unless($template instanceof FormTemplate, 404);
        $this->authorize('update', $template);

        [$editableTemplate, $editableField, $cloned] = $this->editableFieldContext($field, $request->user());

        DB::transaction(function () use ($editableField) {
            $editableField->options()->delete();
            $editableField->delete();
        });

        return redirect()
            ->route('form-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis le champ a été supprimé.'
                : 'Champ supprimé.');
    }

    public function reorderFields(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template_id' => ['required', 'exists:form_templates,id'],
            'field_ids' => ['nullable', 'array', 'min:1'],
            'field_ids.*' => ['integer'],
            'positions' => ['nullable', 'array'],
            'positions.*' => ['integer', 'min:0'],
        ]);

        $template = FormTemplate::query()->findOrFail((int) $validated['template_id']);
        $this->authorize('update', $template);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $request->user());
        $orderedFieldIds = $this->orderedIdsFromPayload($validated);
        $fieldIds = $this->mapFieldIdsForEditableTemplate($editableTemplate, $template, $orderedFieldIds);

        DB::transaction(function () use ($editableTemplate, $fieldIds) {
            foreach (array_values($fieldIds) as $index => $fieldId) {
                $editableTemplate->fields()->whereKey($fieldId)->update(['sort_order' => $index]);
            }
        });

        return redirect()
            ->route('form-builder.edit', $editableTemplate)
            ->with('status', $cloned
                ? 'Une nouvelle version brouillon a été créée puis les champs ont été réordonnés.'
                : 'Champs réordonnés.');
    }

    public function publish(Request $request, FormTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        try {
            $published = $this->publishing->publish($template, $request->user());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('form-builder.edit', $template)
                ->withErrors(['publish' => $exception->getMessage()]);
        }

        return redirect()
            ->route('form-builder.edit', $published)
            ->with('status', 'Formulaire publié et verrouillé pour édition directe.');
    }

    public function archive(Request $request, FormTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $archived = DB::transaction(fn () => $this->publishing->archive($template, $request->user()));

        return redirect()
            ->route('form-builder.index')
            ->with('status', 'Formulaire archivé.');
    }

    /**
     * @return array{0:FormTemplate,1:bool}
     */
    private function ensureEditableTemplate(FormTemplate $template, ?\App\Models\User $actor): array
    {
        $editable = $this->publishing->ensureEditableDraft($template, $actor);

        return [$editable, (int) $editable->id !== (int) $template->id];
    }

    /**
     * @return array{0:FormTemplate,1:FormField,2:bool}
     */
    private function editableFieldContext(FormField $field, ?\App\Models\User $actor): array
    {
        $template = $field->formTemplate;
        abort_unless($template instanceof FormTemplate, 404);

        [$editableTemplate, $cloned] = $this->ensureEditableTemplate($template, $actor);
        if (! $cloned) {
            return [$editableTemplate, $field, false];
        }

        $editableField = $editableTemplate->fields()
            ->where('source_field_id', $field->id)
            ->firstOrFail();

        return [$editableTemplate, $editableField, true];
    }

    /**
     * @param  list<int>  $fieldIds
     * @return list<int>
     */
    private function mapFieldIdsForEditableTemplate(FormTemplate $editableTemplate, FormTemplate $originalTemplate, array $fieldIds): array
    {
        if ((int) $editableTemplate->id === (int) $originalTemplate->id) {
            return array_map('intval', $fieldIds);
        }

        return $editableTemplate->fields()
            ->whereIn('source_field_id', array_map('intval', $fieldIds))
            ->get()
            ->sortBy(fn (FormField $field) => array_search($field->source_field_id, $fieldIds, true))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<int>
     */
    private function orderedIdsFromPayload(array $validated): array
    {
        $ids = array_map('intval', $validated['field_ids'] ?? []);

        if ($ids !== []) {
            return $ids;
        }

        $positions = $validated['positions'] ?? [];
        asort($positions);

        return array_map('intval', array_keys($positions));
    }

    private function lineageTemplates(FormTemplate $template)
    {
        $rootId = (int) ($template->source_template_id ?: $template->id);

        return FormTemplate::query()
            ->where(function ($query) use ($rootId) {
                $query->whereKey($rootId)
                    ->orWhere('source_template_id', $rootId);
            })
            ->orderBy('version')
            ->get();
    }
}
