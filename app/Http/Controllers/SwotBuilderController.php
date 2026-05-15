<?php

namespace App\Http\Controllers;

use App\Domain\Swot\Enums\SwotCategoryType;
use App\Domain\Swot\Enums\SwotImpactLevel;
use App\Domain\Swot\Enums\SwotPriorityLevel;
use App\Models\Department;
use App\Models\SwotCategory;
use App\Models\SwotEntry;
use App\Models\SwotTemplate;
use App\Services\Swot\SwotBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SwotBuilderController extends Controller
{
    public function __construct(
        private SwotBuilderService $builder,
    ) {}

    public function index(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('swot.builder.index', [
            'templates' => $this->builder->library($actor),
            'departmentOptions' => Department::query()->where('active', true)->orderBy('code')->get(),
        ]);
    }

    public function edit(Request $request, SwotTemplate $template): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('swot.builder.edit', [
            'template' => $template,
            'builder' => $this->builder->editorPayload($template),
            'departmentOptions' => Department::query()->where('active', true)->orderBy('code')->get(),
            'categoryTypeLabels' => SwotCategoryType::labels(),
            'impactLabels' => SwotImpactLevel::labels(),
            'priorityLabels' => SwotPriorityLevel::labels(),
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'analysis_scope' => ['nullable', 'string', 'max:40'],
            'is_global' => ['nullable', 'boolean'],
        ]);

        $template = SwotTemplate::query()->create([
            'department_id' => $validated['department_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: Str::slug($validated['name']),
            'code' => $validated['code'] ?? strtoupper(Str::slug($validated['name'], '_')),
            'description' => $validated['description'] ?? null,
            'analysis_scope' => $validated['analysis_scope'] ?? 'mission',
            'active' => true,
            'is_global' => (bool) ($validated['is_global'] ?? false),
            'version' => 1,
            'lifecycle_status' => SwotTemplate::STATUS_DRAFT,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        return redirect()->route('swot-builder.edit', $template)->with('status', 'Template SWOT cree.');
    }

    public function updateTemplate(Request $request, SwotTemplate $template): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'analysis_scope' => ['nullable', 'string', 'max:40'],
            'active' => ['nullable', 'boolean'],
            'is_global' => ['nullable', 'boolean'],
        ]);

        $template->update([
            ...$validated,
            'updated_by' => $actor->id,
            'active' => (bool) ($validated['active'] ?? $template->active),
            'is_global' => (bool) ($validated['is_global'] ?? $template->is_global),
        ]);

        return redirect()->route('swot-builder.edit', $template)->with('status', 'Template SWOT mis a jour.');
    }

    public function storeCategory(Request $request, SwotTemplate $template): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'category_type' => ['required', 'in:'.implode(',', array_keys(SwotCategoryType::labels()))],
            'description' => ['nullable', 'string'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $template->categories()->create([
            'name' => (string) $request->input('name'),
            'code' => $request->input('code'),
            'category_type' => $request->input('category_type'),
            'description' => $request->input('description'),
            'weight' => (float) $request->input('weight', 1),
            'sort_order' => (int) $template->categories()->count(),
        ]);

        return redirect()->route('swot-builder.edit', $template)->with('status', 'Categorie SWOT ajoutee.');
    }

    public function storeEntry(Request $request, SwotTemplate $template): RedirectResponse
    {
        $request->validate([
            'swot_category_id' => ['nullable', 'exists:swot_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'impact_level' => ['required', 'in:'.implode(',', array_keys(SwotImpactLevel::labels()))],
            'priority_level' => ['required', 'in:'.implode(',', array_keys(SwotPriorityLevel::labels()))],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'source_type' => ['nullable', 'string', 'max:80'],
        ]);

        $template->entries()->create([
            'swot_category_id' => $request->input('swot_category_id'),
            'department_id' => $template->department_id,
            'title' => (string) $request->input('title'),
            'description' => $request->input('description'),
            'impact_level' => $request->input('impact_level'),
            'priority_level' => $request->input('priority_level'),
            'weight' => (float) $request->input('weight', 1),
            'source_type' => $request->input('source_type'),
            'is_active' => true,
            'sort_order' => (int) $template->entries()->count(),
        ]);

        return redirect()->route('swot-builder.edit', $template)->with('status', 'Entree SWOT ajoutee.');
    }
}
