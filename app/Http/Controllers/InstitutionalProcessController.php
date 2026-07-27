<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\InstitutionalProcess;
use App\Models\ProcessDomain;
use App\Models\ProcessHistory;
use App\Models\ProcessModuleAccess;
use App\Models\User;
use App\Services\Processes\ProcessAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstitutionalProcessController extends Controller
{
    public function __construct(private readonly ProcessAccessService $access) {}

    public function index(Request $request): View
    {
        abort_unless($this->access->allows($request->user()), 403);
        $query = InstitutionalProcess::query()->visibleTo($request->user())->with(['domain', 'ownerDepartment', 'owner'])->withCount('activities');
        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('code', 'like', $term));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        $visible = InstitutionalProcess::query()->visibleTo($request->user());

        return view('institutional-processes.index', ['processes' => $query->orderBy('name')->paginate(24)->withQueryString(), 'domains' => ProcessDomain::query()->where('active', true)->orderBy('name')->get(), 'role' => $this->access->role($request->user()), 'stats' => ['total' => (clone $visible)->count(), 'published' => (clone $visible)->where('status', 'published')->count(), 'critical' => (clone $visible)->where('criticality', 'critical')->count(), 'undocumented' => (clone $visible)->doesntHave('activities')->count()]]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->access->allows($request->user(), 'contributor'), 403);

        return view('institutional-processes.create', ['domains' => ProcessDomain::query()->where('active', true)->orderBy('name')->get(), 'departments' => Department::query()->where('active', true)->orderBy('name')->get(), 'users' => User::query()->where('active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->access->allows($request->user(), 'contributor'), 403);
        $ownerDepartmentIds = $request->user()->isInstitutionalSuperAdmin() ? Department::query()->pluck('id')->all() : [(int) $request->user()->department_id];
        $data = $request->validate(['domain_id' => ['required', 'exists:process_domains,id'], 'owner_department_id' => ['required', Rule::in($ownerDepartmentIds)], 'owner_user_id' => ['nullable', 'exists:users,id'], 'code' => ['required', 'string', 'max:60', 'unique:institutional_processes,code'], 'name' => ['required', 'string', 'max:255'], 'objective' => ['nullable', 'string'], 'description' => ['nullable', 'string'], 'criticality' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])], 'priority' => ['required', Rule::in(['low', 'normal', 'high', 'critical'])], 'visibility' => ['required', Rule::in(['owner', 'participants', 'institutional'])], 'participant_ids' => ['array'], 'participant_ids.*' => ['integer', 'exists:departments,id']]);
        $process = DB::transaction(function () use ($data, $request) {
            $participants = $data['participant_ids'] ?? [];
            unset($data['participant_ids']);
            $process = InstitutionalProcess::query()->create([...$data, 'created_by' => $request->user()->id]);
            $process->participatingDepartments()->sync($participants);
            $this->history($process, 'created', $request->user());

            return $process;
        });

        return redirect()->route('institutional-processes.show', $process)->with('status', 'Processus créé.');
    }

    public function show(Request $request, InstitutionalProcess $institutionalProcess): View
    {
        abort_unless($this->access->allows($request->user()) && InstitutionalProcess::query()->visibleTo($request->user())->whereKey($institutionalProcess)->exists(), 403);

        return view('institutional-processes.show', ['process' => $institutionalProcess->load(['domain', 'ownerDepartment', 'owner', 'participatingDepartments', 'activities.responsible', 'elements', 'documents', 'kpis', 'history.actor']), 'role' => $this->access->role($request->user()), 'users' => User::query()->where('active', true)->orderBy('name')->get()]);
    }

    public function storeDomain(Request $request): RedirectResponse
    {
        abort_unless($this->access->allows($request->user(), 'administrator'), 403);
        $ownerDepartmentIds = $request->user()->isInstitutionalSuperAdmin() ? Department::query()->pluck('id')->all() : [(int) $request->user()->department_id];
        $data = $request->validate(['owner_department_id' => ['required', Rule::in($ownerDepartmentIds)], 'code' => ['required', 'string', 'max:40', 'unique:process_domains,code'], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string']]);
        ProcessDomain::query()->create([...$data, 'created_by' => $request->user()->id]);

        return back()->with('status', 'Domaine créé.');
    }

    public function addActivity(Request $request, InstitutionalProcess $institutionalProcess): RedirectResponse
    {
        $this->authorizeEdit($request, $institutionalProcess);
        $data = $request->validate(['sequence' => ['required', 'integer', 'min:1'], 'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'], 'responsible_user_id' => ['nullable', 'exists:users,id'], 'produced_documents' => ['nullable', 'string']]);
        $institutionalProcess->activities()->create($data);
        $this->touch($institutionalProcess, $request->user(), 'activity_added');

        return back()->with('status', 'Activité ajoutée.');
    }

    public function addElement(Request $request, InstitutionalProcess $institutionalProcess): RedirectResponse
    {
        $this->authorizeEdit($request, $institutionalProcess);
        $data = $request->validate(['type' => ['required', Rule::in(['input', 'output', 'actor', 'application', 'asset'])], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string']]);
        $institutionalProcess->elements()->create($data);
        $this->touch($institutionalProcess, $request->user(), 'element_added');

        return back()->with('status', 'Élément ajouté.');
    }

    public function addKpi(Request $request, InstitutionalProcess $institutionalProcess): RedirectResponse
    {
        $this->authorizeEdit($request, $institutionalProcess);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'unit' => ['nullable', 'string', 'max:40'], 'target_value' => ['nullable', 'numeric'], 'current_value' => ['nullable', 'numeric'], 'calculation_method' => ['nullable', 'string']]);
        $institutionalProcess->kpis()->create($data);
        $this->touch($institutionalProcess, $request->user(), 'kpi_added');

        return back()->with('status', 'Indicateur ajouté.');
    }

    public function uploadDocument(Request $request, InstitutionalProcess $institutionalProcess): RedirectResponse
    {
        $this->authorizeEdit($request, $institutionalProcess);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'document_type' => ['nullable', 'string', 'max:80'], 'document' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,mp4']]);
        $file = $request->file('document');
        $path = $file->store('process-documents/'.$institutionalProcess->id, 'local');
        $institutionalProcess->documents()->create(['title' => $data['title'], 'document_type' => $data['document_type'] ?? null, 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'uploaded_by' => $request->user()->id]);
        $this->touch($institutionalProcess, $request->user(), 'document_added');

        return back()->with('status', 'Document conservé.');
    }

    public function download(Request $request, InstitutionalProcess $institutionalProcess, int $document)
    {
        abort_unless($this->access->allows($request->user()) && InstitutionalProcess::query()->visibleTo($request->user())->whereKey($institutionalProcess)->exists(), 403);
        $file = $institutionalProcess->documents()->findOrFail($document);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function transition(Request $request, InstitutionalProcess $institutionalProcess): RedirectResponse
    {
        $this->authorizeEdit($request, $institutionalProcess);
        $data = $request->validate(['status' => ['required', Rule::in(['pending_validation', 'published', 'revision', 'archived'])], 'comment' => ['nullable', 'string']]);
        if (in_array($data['status'], ['published', 'archived'], true)) {
            abort_unless($this->access->allows($request->user(), 'validator'), 403);
        }
        $institutionalProcess->update(['status' => $data['status'], 'published_at' => $data['status'] === 'published' ? now() : $institutionalProcess->published_at, 'version' => $data['status'] === 'revision' ? $institutionalProcess->version + 1 : $institutionalProcess->version]);
        $this->history($institutionalProcess, 'status_'.$data['status'], $request->user(), $data['comment'] ?? null);

        return back()->with('status', 'Workflow mis à jour.');
    }

    public function accessAdmin(Request $request): View
    {
        abort_unless($request->user()->isInstitutionalSuperAdmin(), 403);

        return view('institutional-processes.access', ['departments' => Department::query()->with('parent')->where('active', true)->orderBy('name')->get(), 'grants' => ProcessModuleAccess::query()->with(['department', 'grantor'])->get()->keyBy('department_id')]);
    }

    public function updateAccess(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->isInstitutionalSuperAdmin(), 403);
        $data = $request->validate(['active' => ['required', 'boolean'], 'default_role' => ['required', Rule::in(['viewer', 'contributor', 'validator', 'administrator'])], 'inherit_to_children' => ['nullable', 'boolean']]);
        ProcessModuleAccess::query()->updateOrCreate(['department_id' => $department->id], [...$data, 'inherit_to_children' => $request->boolean('inherit_to_children'), 'granted_by' => $request->user()->id]);

        return back()->with('status', 'Habilitation mise à jour.');
    }

    private function authorizeEdit(Request $request, InstitutionalProcess $process): void
    {
        abort_unless($this->access->allows($request->user(), 'contributor'), 403);
        abort_unless($request->user()->isInstitutionalSuperAdmin() || (int) $process->owner_department_id === (int) $request->user()->department_id || $process->participatingDepartments()->whereKey($request->user()->department_id)->exists(), 403);
    }

    private function touch(InstitutionalProcess $process, User $user, string $event): void
    {
        $process->touch();
        $this->history($process, $event, $user);
    }

    private function history(InstitutionalProcess $process, string $event, User $user, ?string $comment = null): void
    {
        ProcessHistory::query()->create(['institutional_process_id' => $process->id, 'event_type' => $event, 'version' => (int) ($process->version ?? 1), 'comment' => $comment, 'actor_id' => $user->id, 'occurred_at' => now()]);
    }
}
