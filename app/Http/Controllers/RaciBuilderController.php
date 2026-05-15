<?php

namespace App\Http\Controllers;

use App\Domain\Raci\Enums\RaciResponsibilityLevel;
use App\Domain\Raci\Enums\RaciRoleType;
use App\Models\Department;
use App\Models\RaciRole;
use App\Models\RaciTemplate;
use App\Services\Raci\RaciMatrixBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RaciBuilderController extends Controller
{
    public function __construct(
        private RaciMatrixBuilderService $builder,
    ) {}

    public function index(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        $templates = RaciTemplate::query()
            ->when(
                ! $actor->canViewAllInstitutionalData() && $actor->department_id !== null,
                fn ($query) => $query->where(function ($inner) use ($actor) {
                    $inner->whereNull('department_id')
                        ->orWhere('department_id', $actor->department_id)
                        ->orWhere('is_global', true);
                })
            )
            ->withCount(['roles', 'assignments', 'matrices'])
            ->with('department')
            ->latest('updated_at')
            ->get();

        return view('raci.builder.index', [
            'templates' => $templates,
            'departmentOptions' => Department::query()->where('active', true)->orderBy('code')->get(),
        ]);
    }

    public function edit(Request $request, RaciTemplate $template): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('raci.builder.matrix', [
            'template' => $template,
            'builder' => $this->builder->editorPayload($template),
            'roleTypeLabels' => RaciRoleType::labels(),
            'responsibilityLabels' => RaciResponsibilityLevel::labels(),
            'departmentOptions' => Department::query()->where('active', true)->orderBy('code')->get(),
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

        $template = RaciTemplate::query()->create([
            'department_id' => $validated['department_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: Str::slug($validated['name']),
            'code' => $validated['code'] ?? strtoupper(Str::slug($validated['name'], '_')),
            'description' => $validated['description'] ?? null,
            'analysis_scope' => $validated['analysis_scope'] ?? 'mission',
            'active' => true,
            'is_global' => (bool) ($validated['is_global'] ?? false),
            'version' => 1,
            'lifecycle_status' => RaciTemplate::STATUS_DRAFT,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        return redirect()->route('raci-builder.edit', $template)->with('status', 'Template RACI cree.');
    }

    public function updateTemplate(Request $request, RaciTemplate $template): RedirectResponse
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

        return redirect()->route('raci-builder.edit', $template)->with('status', 'Template RACI mis a jour.');
    }

    public function storeRole(Request $request, RaciTemplate $template): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'role_type' => ['required', 'in:'.implode(',', array_keys(RaciRoleType::labels()))],
            'responsibility_level' => ['required', 'in:'.implode(',', array_keys(RaciResponsibilityLevel::labels()))],
        ]);

        $template->roles()->create([
            'department_id' => $template->department_id,
            'name' => $request->input('name'),
            'code' => $request->input('code'),
            'role_type' => $request->input('role_type'),
            'responsibility_level' => $request->input('responsibility_level'),
            'sort_order' => (int) $template->roles()->count(),
        ]);

        return redirect()->route('raci-builder.edit', $template)->with('status', 'Role RACI ajoute.');
    }

    public function storeAssignment(Request $request, RaciTemplate $template): RedirectResponse
    {
        $request->validate([
            'raci_role_id' => ['required', 'exists:raci_roles,id'],
            'process_label' => ['required', 'string', 'max:255'],
            'role_type' => ['required', 'in:'.implode(',', array_keys(RaciRoleType::labels()))],
            'responsibility_level' => ['required', 'in:'.implode(',', array_keys(RaciResponsibilityLevel::labels()))],
            'notes' => ['nullable', 'string'],
        ]);

        $template->assignments()->create([
            'raci_role_id' => (int) $request->input('raci_role_id'),
            'process_label' => $request->input('process_label'),
            'process_sort_order' => (int) $template->assignments()->count(),
            'role_type' => $request->input('role_type'),
            'responsibility_level' => $request->input('responsibility_level'),
            'status' => 'template',
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('raci-builder.edit', $template)->with('status', 'Affectation RACI ajoutee au modele.');
    }
}
