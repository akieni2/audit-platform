<?php

namespace App\Http\Controllers\Correspondence;

use App\Http\Controllers\Controller;
use App\Models\CorrespondenceMovement;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CorrespondenceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('accessCorrespondence');

        $query = $this->visibleQuery($request->user())
            ->with(['department', 'assignee'])
            ->latest('received_at');

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(fn (Builder $builder) => $builder
                ->where('reference', 'like', $search)
                ->orWhere('subject', 'like', $search)
                ->orWhere('sender', 'like', $search));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return view('correspondence.index', [
            'records' => $query->paginate(25)->withQueryString(),
            'stats' => [
                'today' => $this->visibleQuery($request->user())->whereDate('received_at', today())->count(),
                'pending' => $this->visibleQuery($request->user())->whereNotIn('status', ['closed', 'archived'])->count(),
                'urgent' => $this->visibleQuery($request->user())->whereIn('urgency', ['urgent', 'very_urgent'])->whereNotIn('status', ['closed', 'archived'])->count(),
                'overdue' => $this->visibleQuery($request->user())->where('deadline_at', '<', now())->whereNotIn('status', ['closed', 'archived'])->count(),
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('accessCorrespondence');
        $departmentIds = request()->user()->managedDepartmentIds();

        return view('correspondence.create', [
            'departments' => Department::query()->whereIn('id', $departmentIds)->where('active', true)->orderBy('name')->get(),
            'users' => User::query()->whereIn('department_id', $departmentIds)->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('accessCorrespondence');
        $departmentIds = $request->user()->managedDepartmentIds();
        $userIds = User::query()->whereIn('department_id', $departmentIds)->where('active', true)->pluck('id')->all();

        $data = $request->validate([
            'direction' => ['required', Rule::in(['incoming', 'outgoing'])],
            'sender' => ['required', 'string', 'max:255'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'document_type' => ['nullable', 'string', 'max:100'],
            'confidentiality' => ['required', Rule::in(['normal', 'confidential', 'secret'])],
            'urgency' => ['required', Rule::in(array_keys(CorrespondenceRecord::urgencyLabels()))],
            'received_at' => ['required', 'date'],
            'current_department_id' => ['nullable', Rule::in($departmentIds)],
            'current_assignee_id' => ['nullable', Rule::in($userIds)],
            'document' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $record = DB::transaction(function () use ($request, $data): CorrespondenceRecord {
            $documentPath = $request->file('document')?->store('correspondence', 'local');
            $deadline = Carbon::parse($data['received_at'])->addMinutes($this->slaMinutes($data['urgency']));

            $record = CorrespondenceRecord::query()->create([
                ...collect($data)->except('document')->all(),
                'deadline_at' => $deadline,
                'document_path' => $documentPath,
                'qr_token' => hash('sha256', Str::uuid()->toString()),
                'status' => ($data['current_department_id'] ?? null) || ($data['current_assignee_id'] ?? null)
                    ? 'assigned'
                    : 'registered',
                'created_by' => $request->user()->id,
            ]);

            $record->update([
                'reference' => sprintf(
                    '%s-%s-%06d',
                    config('dgcpt.correspondence_prefix', 'DGCPT-CR'),
                    $record->received_at->format('Y'),
                    $record->id,
                ),
            ]);

            CorrespondenceMovement::query()->create([
                'correspondence_record_id' => $record->id,
                'event_type' => 'registered',
                'to_department_id' => $record->current_department_id,
                'to_user_id' => $record->current_assignee_id,
                'notes' => 'Courrier enregistré dans la GEC.',
                'occurred_at' => now(),
                'actor_id' => $request->user()->id,
            ]);

            return $record;
        });

        return redirect()->route('correspondence.show', $record)
            ->with('status', 'Courrier enregistré sous la référence '.$record->reference.'.');
    }

    public function show(Request $request, CorrespondenceRecord $correspondence): View
    {
        Gate::authorize('accessCorrespondence');
        abort_unless($this->visibleQuery($request->user())->whereKey($correspondence)->exists(), 403);
        $departmentIds = $request->user()->managedDepartmentIds();

        return view('correspondence.show', [
            'record' => $correspondence->load([
                'department', 'assignee', 'creator',
                'movements.actor', 'movements.toDepartment', 'movements.toUser',
                'administrativeTasks.assignee',
            ]),
            'departments' => Department::query()->whereIn('id', $departmentIds)->where('active', true)->orderBy('name')->get(),
            'users' => User::query()->whereIn('department_id', $departmentIds)->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function assign(Request $request, CorrespondenceRecord $correspondence): RedirectResponse
    {
        Gate::authorize('accessCorrespondence');
        abort_unless($this->visibleQuery($request->user())->whereKey($correspondence)->exists(), 403);
        $departmentIds = $request->user()->managedDepartmentIds();
        $userIds = User::query()->whereIn('department_id', $departmentIds)->where('active', true)->pluck('id')->all();

        $data = $request->validate([
            'department_id' => ['nullable', Rule::in($departmentIds)],
            'assignee_id' => ['nullable', Rule::in($userIds)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $correspondence, $data): void {
            CorrespondenceMovement::query()->create([
                'correspondence_record_id' => $correspondence->id,
                'event_type' => 'assigned',
                'from_department_id' => $correspondence->current_department_id,
                'to_department_id' => $data['department_id'] ?? null,
                'from_user_id' => $correspondence->current_assignee_id,
                'to_user_id' => $data['assignee_id'] ?? null,
                'notes' => $data['notes'] ?? 'Nouvelle affectation.',
                'occurred_at' => now(),
                'actor_id' => $request->user()->id,
            ]);

            $correspondence->update([
                'current_department_id' => $data['department_id'] ?? null,
                'current_assignee_id' => $data['assignee_id'] ?? null,
                'status' => 'assigned',
            ]);
        });

        return back()->with('status', 'Courrier affecté et mouvement historisé.');
    }

    public function updateStatus(Request $request, CorrespondenceRecord $correspondence): RedirectResponse
    {
        Gate::authorize('accessCorrespondence');
        abort_unless($this->visibleQuery($request->user())->whereKey($correspondence)->exists(), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(CorrespondenceRecord::statusLabels()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $correspondence->update(['status' => $data['status']]);
        CorrespondenceMovement::query()->create([
            'correspondence_record_id' => $correspondence->id,
            'event_type' => 'status_'.$data['status'],
            'from_department_id' => $correspondence->current_department_id,
            'to_department_id' => $correspondence->current_department_id,
            'from_user_id' => $correspondence->current_assignee_id,
            'to_user_id' => $correspondence->current_assignee_id,
            'notes' => $data['notes'] ?? 'Statut mis à jour.',
            'occurred_at' => now(),
            'actor_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Statut du courrier mis à jour.');
    }

    public function download(Request $request, CorrespondenceRecord $correspondence): StreamedResponse
    {
        Gate::authorize('accessCorrespondence');
        abort_unless($this->visibleQuery($request->user())->whereKey($correspondence)->exists(), 403);
        abort_unless($correspondence->document_path && Storage::disk('local')->exists($correspondence->document_path), 404);

        return Storage::disk('local')->download(
            $correspondence->document_path,
            $correspondence->reference.'.pdf',
        );
    }

    private function visibleQuery(User $user): Builder
    {
        $query = CorrespondenceRecord::query();
        if ($user->isInstitutionalSuperAdmin() || $user->canSuperviseAllDepartments()) {
            return $query;
        }

        $departmentIds = $user->managedDepartmentIds();

        return $query->where(function (Builder $builder) use ($user, $departmentIds): void {
            $builder->where('created_by', $user->id)
                ->orWhere('current_assignee_id', $user->id)
                ->when($departmentIds !== [], fn (Builder $q) => $q->orWhereIn('current_department_id', $departmentIds));
        });
    }

    private function slaMinutes(string $urgency): int
    {
        return match ($urgency) {
            'very_urgent' => 4 * 60,
            'urgent' => 12 * 60,
            'normal' => 24 * 60,
            'standard' => 3 * 24 * 60,
            default => 7 * 24 * 60,
        };
    }
}
