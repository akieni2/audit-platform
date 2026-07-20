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
use App\Support\OrganizationStructure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Administration de l'organigramme institutionnel DGCPT.
 */
class DepartmentManagementController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->with(['supervisor', 'parent'])
            ->withCount(['users' => fn ($q) => $q->where('active', true)])
            ->orderBy('code')
            ->paginate(25);

        return view('iam.admin.departments.index', [
            'departments' => $departments,
            'departmentTree' => $this->departmentTree(),
        ]);
    }

    public function organigramme(): View
    {
        $this->authorize('viewAny', Department::class);

        return view('iam.admin.departments.organigramme', [
            'departmentTree' => $this->departmentTree(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('iam.admin.departments.create', $this->formData());
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $department = Department::query()->create(
            $this->departmentPayload(
                $validated,
                $request->boolean('active', true),
                $request->boolean('executive_visibility')
            )
        );

        $topManagerPassword = $this->createTopManagerIfRequested($validated, $department);

        return redirect()
            ->route('admin.departments.index')
            ->with('status', 'Structure créée.'.($topManagerPassword ? ' Mot de passe temporaire du responsable : '.$topManagerPassword : ''));
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

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

        $department->update(
            $this->departmentPayload(
                $validated,
                $request->boolean('active', $department->active),
                $request->boolean('executive_visibility', $department->executive_visibility)
            )
        );

        $topManagerPassword = $this->createTopManagerIfRequested($validated, $department->fresh());

        return redirect()
            ->route('admin.departments.edit', $department)
            ->with('status', 'Structure enregistrée.'.($topManagerPassword ? ' Mot de passe temporaire du responsable : '.$topManagerPassword : ''));
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $department->update(['active' => false]);

        return redirect()
            ->route('admin.departments.index')
            ->with('status', 'Département désactivé.');
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

    /**
     * @return \Illuminate\Support\Collection<int, Department>
     */
    private function departmentTree()
    {
        return Department::query()
            ->with(['children.children.children.children', 'supervisor'])
            ->whereNull('parent_department_id')
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
