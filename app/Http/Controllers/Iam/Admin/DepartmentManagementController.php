<?php

namespace App\Http\Controllers\Iam\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Administration des pôles et départements institutionnels.
 */
class DepartmentManagementController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->with('supervisor')
            ->withCount(['users' => fn ($q) => $q->where('active', true)])
            ->orderBy('code')
            ->paginate(25);

        return view('iam.admin.departments.index', [
            'departments' => $departments,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('iam.admin.departments.create', [
            'supervisors' => User::query()->where('active', true)->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);

        Department::query()->create($data);

        return redirect()
            ->route('admin.departments.index')
            ->with('status', 'Département créé.');
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('iam.admin.departments.edit', [
            'department' => $department,
            'supervisors' => User::query()->where('active', true)->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', $department->active);

        $department->update($data);

        return redirect()
            ->route('admin.departments.edit', $department)
            ->with('status', 'Département enregistré.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $department->update(['active' => false]);

        return redirect()
            ->route('admin.departments.index')
            ->with('status', 'Département désactivé.');
    }
}
