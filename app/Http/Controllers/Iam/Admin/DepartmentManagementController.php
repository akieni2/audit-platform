<?php

namespace App\Http\Controllers\Iam\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\MethodologyTemplate;
use App\Models\Role;
use App\Models\Taxonomy;
use App\Models\User;
use App\Services\Governance\DepartmentAuditEnvironmentService;
use App\Services\Governance\OrganizationDeletionService;
use App\Support\OrganizationStructure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Administration de l'organigramme institutionnel DGCPT.
 */
class DepartmentManagementController extends Controller
{
    public function __construct(
        private readonly DepartmentAuditEnvironmentService $auditEnvironments,
        private readonly OrganizationDeletionService $organizationDeletion,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Department::class);

        $actor = request()->user();
        $departments = Department::query()
            ->when(! $actor->canAdministerOrganization(), fn ($query) => $query->where('supervisor_user_id', $actor->id))
            ->with(['supervisor', 'parent', 'defaultMethodologyTemplate', 'tenantContext'])
            ->withCount(['users' => fn ($q) => $q->where('active', true)])
            ->orderBy('code')
            ->paginate(25);

        return view('iam.admin.departments.index', [
            'departments' => $departments,
            'departmentTree' => $this->departmentTree($actor),
        ]);
    }

    public function organigramme(): View
    {
        $this->authorize('viewAny', Department::class);
        $actor = request()->user();

        return view('iam.admin.departments.organigramme', [
            'departmentTree' => $this->departmentTree($actor),
            'structureTypes' => OrganizationStructure::typeOptions(),
            'positionTypes' => OrganizationStructure::positionOptions(),
            'methodologies' => MethodologyTemplate::query()->where('active', true)->orderBy('name')->get(),
            'canBuildOrganigramme' => $actor->canBuildFunctionalOrganization(),
            'isGlobalOrganigramme' => $actor->canViewGlobalOrganization(),
        ]);
    }

    public function visualStore(Request $request): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->canBuildFunctionalOrganization(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', 'unique:departments,code'],
            'type' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(OrganizationStructure::typeOptions()))],
            'parent_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'default_methodology_template_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('methodology_templates', 'id')->where('active', true)],
        ]);

        $this->validateVisualPlacement($validated['type'], $validated['parent_department_id'] ?? null);
        $this->authorizeVisualParent($actor, $validated['parent_department_id'] ?? null);

        if (OrganizationStructure::requiresAuditMethodology($validated['type']) && empty($validated['default_methodology_template_id'])) {
            return response()->json(['message' => 'Le référentiel d’audit est obligatoire pour cette structure.'], 422);
        }

        $department = DB::transaction(function () use ($validated, $request): Department {
            $department = Department::query()->create([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'type' => $validated['type'],
                'active' => true,
                'parent_department_id' => $validated['parent_department_id'] ?? null,
                'default_methodology_template_id' => $validated['default_methodology_template_id'] ?? null,
                'executive_visibility' => true,
                'intelligence_profile' => [
                    'position_title' => OrganizationStructure::defaultHeadTitle($validated['type']),
                    'position_activities' => [],
                ],
            ]);
            $this->provisionAuditEnvironment($department, $request->user());

            return $department;
        });

        return response()->json(['message' => 'Structure créée.', 'id' => $department->id], 201);
    }

    public function visualMove(Request $request, Department $department): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->canBuildFunctionalOrganization(), 403);

        $validated = $request->validate([
            'parent_department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);
        $parentId = $validated['parent_department_id'] ?? null;
        $this->authorizeVisualDepartment($actor, $department, false);
        $this->authorizeVisualParent($actor, $parentId);

        if ($parentId !== null && (int) $parentId === (int) $department->id) {
            return response()->json(['message' => 'Une structure ne peut pas devenir sa propre parente.'], 422);
        }

        $parent = $parentId !== null ? Department::query()->findOrFail($parentId) : null;
        if ($parent?->isDescendantOf($department)) {
            return response()->json(['message' => 'Ce déplacement créerait une boucle hiérarchique.'], 422);
        }

        $this->validateVisualPlacement($department->type, $parentId);
        $department->update(['parent_department_id' => $parentId]);

        return response()->json(['message' => 'Organigramme actualisé.']);
    }

    public function visualPosition(Request $request, Department $department): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->canBuildFunctionalOrganization(), 403);
        $this->authorizeVisualDepartment($actor, $department, true);
        $validated = $request->validate([
            'position_title' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(OrganizationStructure::positionOptions()))],
        ]);

        $department->update([
            'intelligence_profile' => array_replace_recursive($department->intelligence_profile ?? [], [
                'position_title' => $validated['position_title'],
            ]),
        ]);

        return response()->json(['message' => 'Fonction dirigeante affectée.']);
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('iam.admin.departments.create', $this->formData());
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        [$department, $topManagerPassword] = DB::transaction(function () use ($validated, $request): array {
            $department = Department::query()->create(
                $this->departmentPayload(
                    $validated,
                    $request->boolean('active', true),
                    $request->boolean('executive_visibility')
                )
            );
            $password = $this->createTopManagerIfRequested($validated, $department);
            $this->provisionAuditEnvironment($department, $request->user());

            return [$department, $password];
        });

        return redirect()
            ->route('admin.departments.index')
            ->with('status', 'Structure créée.'.($topManagerPassword ? ' Mot de passe temporaire du responsable : '.$topManagerPassword : ''));
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        if (! request()->user()->canAdministerOrganization()) {
            return view('iam.admin.departments.audit-environment', [
                'department' => $department->load(['defaultMethodologyTemplate', 'tenantContext']),
                'methodologies' => MethodologyTemplate::query()->where('active', true)->orderBy('name')->get(),
                'taxonomies' => Taxonomy::query()->where('active', true)->orderByDesc('is_national')->orderBy('name')->get(),
            ]);
        }

        return view('iam.admin.departments.edit', $this->formData([
            'department' => $department->load(['parent', 'supervisor']),
            'departments' => Department::query()
                ->where('active', true)
                ->whereKeyNot($department->id)
                ->orderBy('code')
                ->get(),
        ]));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $validated = $request->validated();

        if (! $request->user()->canAdministerOrganization()) {
            $department->update([
                'default_methodology_template_id' => $validated['default_methodology_template_id'],
                'default_taxonomy_id' => $validated['default_taxonomy_id'] ?? null,
            ]);
            $this->provisionAuditEnvironment($department->fresh(), $request->user());

            return redirect()
                ->route('admin.departments.edit', $department)
                ->with('status', 'Référentiel et espace d’audit enregistrés.');
        }

        $topManagerPassword = DB::transaction(function () use ($validated, $request, $department): ?string {
            $department->update(
                $this->departmentPayload(
                    $validated,
                    $request->boolean('active', $department->active),
                    $request->boolean('executive_visibility', $department->executive_visibility)
                )
            );
            $password = $this->createTopManagerIfRequested($validated, $department->fresh());
            $this->provisionAuditEnvironment($department->fresh(), $request->user());

            return $password;
        });

        return redirect()
            ->route('admin.departments.edit', $department)
            ->with('status', 'Structure enregistrée.'.($topManagerPassword ? ' Mot de passe temporaire du responsable : '.$topManagerPassword : ''));
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $request->validate([
            'confirmation_code' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($department): void {
                if (strtoupper(trim((string) $value)) !== strtoupper($department->code)) {
                    $fail('Le code de confirmation ne correspond pas à la structure.');
                }
            }],
        ]);

        $result = $this->organizationDeletion->deleteTree($department);

        return redirect()
            ->route('admin.departments.index')
            ->with('status', $result['departments'].' structure(s) supprimée(s) définitivement.');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function formData(array $extra = []): array
    {
        return array_merge([
            'supervisors' => User::query()->where('active', true)->orderBy('name')->limit(500)->get(),
            'departments' => Department::query()->where('active', true)->orderBy('code')->get(),
            'methodologies' => MethodologyTemplate::query()->where('active', true)->orderBy('name')->get(),
            'taxonomies' => Taxonomy::query()->where('active', true)->orderByDesc('is_national')->orderBy('name')->get(),
            'roles' => Role::query()->where('active', true)->orderByDesc('hierarchy_level')->get(),
            'structureTypes' => OrganizationStructure::typeOptions(),
        ], $extra);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function departmentPayload(array $validated, bool $active, bool $executiveVisibility): array
    {
        return [
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? null,
            'active' => $active,
            'parent_department_id' => $validated['parent_department_id'] ?? null,
            'governance_scope' => $validated['governance_scope'] ?? null,
            'default_methodology_template_id' => $validated['default_methodology_template_id'] ?? null,
            'default_taxonomy_id' => $validated['default_taxonomy_id'] ?? null,
            'executive_visibility' => $executiveVisibility,
            'supervisor_user_id' => $validated['supervisor_user_id'] ?? null,
            'accent_color' => $validated['accent_color'] ?? null,
            'logo_path' => $validated['logo_path'] ?? null,
            'intelligence_profile' => [
                'position_title' => ($validated['position_title'] ?? null) ?: OrganizationStructure::defaultHeadTitle($validated['type'] ?? null),
                'position_description' => $validated['position_description'] ?? null,
                'position_activities' => $this->lines($validated['position_activities'] ?? null),
                'top_manager_profile' => [
                    'title' => $validated['top_manager_title'] ?? null,
                    'name' => $validated['top_manager_name'] ?? null,
                    'email' => $validated['top_manager_email'] ?? null,
                    'role_id' => $validated['top_manager_role_id'] ?? null,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createTopManagerIfRequested(array $validated, ?Department $department): ?string
    {
        if ($department === null || ! ($validated['create_top_manager'] ?? false) || empty($validated['top_manager_email'])) {
            return null;
        }

        $password = 'TmpOrg'.Str::random(12).'Aa1!';
        $department->loadMissing('parent');
        $recommendedRole = OrganizationStructure::recommendedRoleSlug(
            $department->type,
            $department->parent?->type
        );
        $roleId = $validated['top_manager_role_id'] ?? null;

        if ($roleId === null && $recommendedRole !== null) {
            $roleId = Role::query()
                ->where('active', true)
                ->where('slug', $recommendedRole)
                ->value('id');
        }

        $positionTitle = ($validated['top_manager_title'] ?? null)
            ?: OrganizationStructure::defaultHeadTitle($department->type);

        $user = User::query()->create([
            'name' => $validated['top_manager_name'] ?: ($positionTitle.' '.$department->code),
            'email' => $validated['top_manager_email'],
            'password' => Hash::make($password),
            'password_changed_at' => now(),
            'must_change_password' => true,
            'department_id' => $department->id,
            'role_id' => $roleId,
            'position' => $positionTitle,
            'active' => true,
            'role' => $recommendedRole ?? 'manager',
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $department->update(['supervisor_user_id' => $user->id]);

        return $password;
    }

    private function provisionAuditEnvironment(Department $department, ?User $actor): void
    {
        if ($department->default_methodology_template_id === null) {
            return;
        }

        $methodology = MethodologyTemplate::query()->findOrFail($department->default_methodology_template_id);
        $this->auditEnvironments->provision($department, $methodology, $actor);
    }

    private function validateVisualPlacement(string $type, ?int $parentId): void
    {
        if (OrganizationStructure::requiresParent($type) && $parentId === null) {
            abort(response()->json(['message' => 'Cette structure doit être rattachée à une structure parente.'], 422));
        }

        if ($parentId === null) {
            return;
        }

        $parentType = Department::query()->whereKey($parentId)->value('type');
        if (! in_array($parentType, OrganizationStructure::allowedParentTypes($type), true)) {
            abort(response()->json(['message' => 'Ce niveau hiérarchique ne peut pas être déposé sur cette structure.'], 422));
        }
    }

    private function authorizeVisualParent(User $actor, ?int $parentId): void
    {
        if ($actor->canAdministerOrganization()) {
            return;
        }

        abort_if($parentId === null || $actor->department_id === null, 403);
        $parent = Department::query()->findOrFail($parentId);
        $root = Department::query()->findOrFail($actor->department_id);
        abort_unless((int) $parent->id === (int) $root->id || $parent->isDescendantOf($root), 403);
    }

    private function authorizeVisualDepartment(User $actor, Department $department, bool $allowRoot): void
    {
        if ($actor->canAdministerOrganization()) {
            return;
        }

        abort_if($actor->department_id === null, 403);
        $root = Department::query()->findOrFail($actor->department_id);
        $isRoot = (int) $department->id === (int) $root->id;
        abort_unless(($allowRoot && $isRoot) || $department->isDescendantOf($root), 403);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Department>
     */
    private function departmentTree(?User $actor = null)
    {
        return Department::query()
            ->with(['children.children.children.children', 'supervisor'])
            ->when(
                $actor !== null && ! $actor->canViewGlobalOrganization(),
                fn ($query) => $query->whereKey($actor->department_id),
                fn ($query) => $query->whereNull('parent_department_id')
            )
            ->orderByRaw("case when code in ('DG', 'DGTCP', 'DGCPT', 'ADMIN_CENT') then 0 else 1 end")
            ->orderBy('code')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private function lines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
