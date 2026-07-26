<?php

namespace App\Http\Controllers\AdministrativeWork;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeTask;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdministrativeTaskController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('accessAdministrativeWork');

        $query = $this->visibleQuery($request->user())
            ->with(['department', 'assignee', 'owner', 'correspondenceRecord'])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return view('administrative-work.index', [
            'tasks' => $query->paginate(25)->withQueryString(),
            'stats' => [
                'active' => $this->visibleQuery($request->user())->whereNotIn('status', ['validated', 'closed'])->count(),
                'mine' => $this->visibleQuery($request->user())->where('assignee_id', $request->user()->id)->whereNotIn('status', ['validated', 'closed'])->count(),
                'overdue' => $this->visibleQuery($request->user())->where('due_at', '<', now())->whereNotIn('status', ['validated', 'closed'])->count(),
                'validated' => $this->visibleQuery($request->user())->where('status', 'validated')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('accessAdministrativeWork');
        $departmentIds = $request->user()->managedDepartmentIds();

        $correspondence = null;
        if ($request->integer('correspondence') && $request->user()->canAccessCorrespondenceModule()) {
            $correspondenceQuery = CorrespondenceRecord::query()
                ->whereKey($request->integer('correspondence'));

            if (! $request->user()->canSuperviseAllDepartments()) {
                $correspondenceQuery->where(function (Builder $query) use ($request, $departmentIds): void {
                    $query->whereIn('current_department_id', $departmentIds)
                        ->orWhere('current_assignee_id', $request->user()->id)
                        ->orWhere('created_by', $request->user()->id);
                });
            }

            $correspondence = $correspondenceQuery->first();
        }

        return view('administrative-work.create', [
            'departments' => Department::query()->whereIn('id', $departmentIds)->where('active', true)->orderBy('name')->get(),
            'users' => User::query()->whereIn('department_id', $departmentIds)->where('active', true)->orderBy('name')->get(),
            'correspondence' => $correspondence,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('accessAdministrativeWork');
        $departmentIds = $request->user()->managedDepartmentIds();
        $userIds = User::query()->whereIn('department_id', $departmentIds)->where('active', true)->pluck('id')->all();

        $data = $request->validate([
            'correspondence_record_id' => ['nullable', 'exists:correspondence_records,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(array_keys(AdministrativeTask::priorityLabels()))],
            'department_id' => ['nullable', Rule::in($departmentIds)],
            'owner_id' => ['required', Rule::in($userIds)],
            'assignee_id' => ['nullable', Rule::in($userIds)],
            'due_at' => ['nullable', 'date'],
        ]);

        if (! empty($data['correspondence_record_id'])) {
            abort_unless($request->user()->canAccessCorrespondenceModule(), 403);
            $record = CorrespondenceRecord::query()->findOrFail($data['correspondence_record_id']);
            abort_unless($request->user()->canSuperviseAllDepartments()
                || in_array((int) $record->current_department_id, $departmentIds, true)
                || (int) $record->current_assignee_id === (int) $request->user()->id
                || (int) $record->created_by === (int) $request->user()->id, 403);
        }

        $task = AdministrativeTask::query()->create([
            ...$data,
            'status' => ! empty($data['assignee_id']) ? 'assigned' : 'draft',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('administrative-work.show', $task)
            ->with('status', 'Instruction administrative créée.');
    }

    public function show(Request $request, AdministrativeTask $administrativeTask): View
    {
        Gate::authorize('accessAdministrativeWork');
        abort_unless($this->visibleQuery($request->user())->whereKey($administrativeTask)->exists(), 403);

        return view('administrative-work.show', [
            'task' => $administrativeTask->load([
                'department', 'owner', 'assignee', 'creator', 'correspondenceRecord',
            ]),
        ]);
    }

    public function updateStatus(Request $request, AdministrativeTask $administrativeTask): RedirectResponse
    {
        Gate::authorize('accessAdministrativeWork');
        abort_unless($this->visibleQuery($request->user())->whereKey($administrativeTask)->exists(), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(AdministrativeTask::statusLabels()))],
        ]);

        $administrativeTask->update([
            'status' => $data['status'],
            'completed_at' => in_array($data['status'], ['validated', 'closed'], true) ? now() : null,
        ]);

        return back()->with('status', 'État de la tâche mis à jour.');
    }

    private function visibleQuery(User $user): Builder
    {
        $query = AdministrativeTask::query();
        if ($user->isInstitutionalSuperAdmin() || $user->canSuperviseAllDepartments()) {
            return $query;
        }

        $departmentIds = $user->managedDepartmentIds();

        return $query->where(function (Builder $builder) use ($user, $departmentIds): void {
            $builder->where('created_by', $user->id)
                ->orWhere('owner_id', $user->id)
                ->orWhere('assignee_id', $user->id)
                ->when($departmentIds !== [], fn (Builder $q) => $q->orWhereIn('department_id', $departmentIds));
        });
    }
}
