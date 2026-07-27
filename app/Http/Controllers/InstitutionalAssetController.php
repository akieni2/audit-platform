<?php

namespace App\Http\Controllers;

use App\Models\AssetModuleAccess;
use App\Models\Department;
use App\Models\InstitutionalAsset;
use App\Models\InstitutionalAssetCategory;
use App\Models\InstitutionalAssetHistory;
use App\Models\InstitutionalProcess;
use App\Models\User;
use App\Services\Assets\AssetAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstitutionalAssetController extends Controller
{
    public function __construct(private readonly AssetAccessService $access) {}

    public function index(Request $r): View
    {
        $this->guard($r);
        $q = InstitutionalAsset::visibleTo($r->user())->with(['category', 'ownerDepartment', 'owner'])->withCount(['dependencies', 'controls']);
        if ($r->filled('q')) {
            $s = '%'.$r->string('q').'%';
            $q->where(fn (Builder $b) => $b->where('name', 'like', $s)->orWhere('asset_tag', 'like', $s)->orWhere('serial_number', 'like', $s));
        }if ($r->filled('criticality')) {
            $q->where('criticality', $r->string('criticality'));
        }$base = InstitutionalAsset::visibleTo($r->user());

        return view('institutional-assets.index', ['assets' => $q->orderBy('name')->paginate(24)->withQueryString(), 'role' => $this->access->role($r->user()), 'stats' => ['total' => (clone $base)->count(), 'critical' => (clone $base)->where('criticality', 'critical')->count(), 'ownerless' => (clone $base)->whereNull('owner_user_id')->count(), 'no_backup' => (clone $base)->where('has_backup', false)->count(), 'obsolete' => (clone $base)->where('obsolete', true)->count(), 'spof' => (clone $base)->where('single_point_of_failure', true)->count()]]);
    }

    public function create(Request $r): View
    {
        $this->guard($r, 'contributor');

        return view('institutional-assets.create', ['categories' => InstitutionalAssetCategory::where('active', true)->orderBy('name')->get(), 'departments' => Department::where('active', true)->orderBy('name')->get(), 'users' => User::where('active', true)->orderBy('name')->get()]);
    }

    public function store(Request $r): RedirectResponse
    {
        $this->guard($r, 'contributor');
        $owners = $r->user()->isInstitutionalSuperAdmin() ? Department::pluck('id')->all() : [(int) $r->user()->department_id];
        $data = $r->validate($this->rules($owners));
        [$score,$level] = $this->score($data);
        if (! $this->access->allows($r->user(), 'validator')) {
            $data['status'] = 'draft';
        }
        $asset = DB::transaction(function () use ($data, $r, $score, $level) {
            $participants = $data['participant_ids'] ?? [];
            $processes = $data['process_ids'] ?? [];
            unset($data['participant_ids'],$data['process_ids']);
            $asset = InstitutionalAsset::create([...$data, 'impact_score' => max($data['availability_score'], $data['confidentiality_score'], $data['integrity_score'], $data['traceability_score']), 'criticality_score' => $score, 'criticality' => $level, 'created_by' => $r->user()->id]);
            $asset->participatingDepartments()->sync($participants);
            $asset->processes()->sync($processes);
            $this->history($asset, 'created', $r->user());

            return $asset;
        });

        return redirect()->route('institutional-assets.show', $asset)->with('status', 'Actif enregistré.');
    }

    public function show(Request $r, InstitutionalAsset $institutionalAsset): View
    {
        $this->guard($r);
        abort_unless(InstitutionalAsset::visibleTo($r->user())->whereKey($institutionalAsset)->exists(), 403);

        return view('institutional-assets.show', ['asset' => $institutionalAsset->load(['category', 'ownerDepartment', 'owner', 'participatingDepartments', 'dependencies.category', 'dependentAssets', 'processes', 'controls.responsible', 'documents', 'history.actor']), 'role' => $this->access->role($r->user()), 'assets' => InstitutionalAsset::visibleTo($r->user())->whereKeyNot($institutionalAsset)->orderBy('name')->get(), 'processes' => InstitutionalProcess::visibleTo($r->user())->orderBy('name')->get(), 'users' => User::where('active', true)->orderBy('name')->get()]);
    }

    public function addDependency(Request $r, InstitutionalAsset $institutionalAsset): RedirectResponse
    {
        $this->edit($r, $institutionalAsset);
        $d = $r->validate(['depends_on_asset_id' => ['required', 'exists:institutional_assets,id', Rule::notIn([$institutionalAsset->id])], 'dependency_type' => ['nullable', 'string', 'max:100'], 'description' => ['nullable', 'string'], 'critical' => ['nullable', 'boolean']]);
        $id = $d['depends_on_asset_id'];
        unset($d['depends_on_asset_id']);
        $institutionalAsset->dependencies()->syncWithoutDetaching([$id => [...$d, 'critical' => $r->boolean('critical')]]);
        $this->history($institutionalAsset, 'dependency_added', $r->user());

        return back()->with('status', 'Dépendance ajoutée.');
    }

    public function addControl(Request $r, InstitutionalAsset $institutionalAsset): RedirectResponse
    {
        $this->edit($r, $institutionalAsset);
        $d = $r->validate(['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'status' => ['required', Rule::in(['planned', 'implemented', 'ineffective', 'not_applicable'])], 'responsible_user_id' => ['nullable', 'exists:users,id'], 'reviewed_at' => ['nullable', 'date']]);
        $institutionalAsset->controls()->create($d);
        $this->history($institutionalAsset, 'control_added', $r->user());

        return back()->with('status', 'Contrôle ajouté.');
    }

    public function linkProcess(Request $r, InstitutionalAsset $institutionalAsset): RedirectResponse
    {
        $this->edit($r, $institutionalAsset);
        $d = $r->validate(['institutional_process_id' => ['required', 'exists:institutional_processes,id']]);
        $institutionalAsset->processes()->syncWithoutDetaching([$d['institutional_process_id']]);
        $this->history($institutionalAsset, 'process_linked', $r->user());

        return back()->with('status', 'Processus associé.');
    }

    public function uploadDocument(Request $r, InstitutionalAsset $institutionalAsset): RedirectResponse
    {
        $this->edit($r, $institutionalAsset);
        $d = $r->validate(['title' => ['required', 'string', 'max:255'], 'document_type' => ['nullable', 'string', 'max:80'], 'document' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg']]);
        $file = $r->file('document');
        $path = $file->store('asset-documents/'.$institutionalAsset->id, 'local');
        $institutionalAsset->documents()->create(['title' => $d['title'], 'document_type' => $d['document_type'] ?? null, 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'uploaded_by' => $r->user()->id]);
        $this->history($institutionalAsset, 'document_added', $r->user());

        return back()->with('status', 'Document ajouté.');
    }

    public function download(Request $r, InstitutionalAsset $institutionalAsset, int $document)
    {
        $this->guard($r);
        abort_unless(InstitutionalAsset::visibleTo($r->user())->whereKey($institutionalAsset)->exists(), 403);
        $d = $institutionalAsset->documents()->findOrFail($document);

        return Storage::disk('local')->download($d->path, $d->original_name);
    }

    public function updateStatus(Request $r, InstitutionalAsset $institutionalAsset): RedirectResponse
    {
        $this->edit($r, $institutionalAsset);
        $d = $r->validate(['status' => ['required', Rule::in(array_keys(InstitutionalAsset::statusLabels()))], 'comment' => ['nullable', 'string']]);
        if (in_array($d['status'], ['active', 'archived'], true)) {
            abort_unless($this->access->allows($r->user(), 'validator'), 403);
        }$institutionalAsset->update(['status' => $d['status']]);
        $this->history($institutionalAsset, 'status_'.$d['status'], $r->user(), $d['comment'] ?? null);

        return back()->with('status', 'État mis à jour.');
    }

    public function storeCategory(Request $r): RedirectResponse
    {
        $this->guard($r, 'administrator');
        $owners = $r->user()->isInstitutionalSuperAdmin() ? Department::pluck('id')->all() : [(int) $r->user()->department_id];
        $d = $r->validate(['owner_department_id' => ['required', Rule::in($owners)], 'code' => ['required', 'string', 'max:40', 'unique:institutional_asset_categories,code'], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string']]);
        InstitutionalAssetCategory::create([...$d, 'created_by' => $r->user()->id]);

        return back()->with('status', 'Catégorie créée.');
    }

    public function accessAdmin(Request $r): View
    {
        abort_unless($r->user()->isInstitutionalSuperAdmin(), 403);

        return view('institutional-assets.access', ['departments' => Department::with('parent')->where('active', true)->orderBy('name')->get(), 'grants' => AssetModuleAccess::all()->keyBy('department_id')]);
    }

    public function updateAccess(Request $r, Department $department): RedirectResponse
    {
        abort_unless($r->user()->isInstitutionalSuperAdmin(), 403);
        $d = $r->validate(['active' => ['required', 'boolean'], 'default_role' => ['required', Rule::in(['viewer', 'contributor', 'validator', 'administrator'])], 'inherit_to_children' => ['nullable', 'boolean']]);
        AssetModuleAccess::updateOrCreate(['department_id' => $department->id], [...$d, 'inherit_to_children' => $r->boolean('inherit_to_children'), 'granted_by' => $r->user()->id]);

        return back()->with('status', 'Habilitation mise à jour.');
    }

    private function rules(array $owners): array
    {
        return ['category_id' => ['required', 'exists:institutional_asset_categories,id'], 'owner_department_id' => ['required', Rule::in($owners)], 'owner_user_id' => ['nullable', 'exists:users,id'], 'asset_tag' => ['required', 'string', 'max:60', 'unique:institutional_assets,asset_tag'], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'location' => ['nullable', 'string', 'max:255'], 'commissioned_at' => ['nullable', 'date'], 'manufacturer' => ['nullable', 'string', 'max:255'], 'model' => ['nullable', 'string', 'max:255'], 'serial_number' => ['nullable', 'string', 'max:255'], 'condition' => ['required', Rule::in(['new', 'good', 'degraded', 'failed', 'retired'])], 'status' => ['required', Rule::in(array_keys(InstitutionalAsset::statusLabels()))], 'estimated_value' => ['nullable', 'numeric', 'min:0'], 'availability_score' => ['required', 'integer', 'between:1,5'], 'confidentiality_score' => ['required', 'integer', 'between:1,5'], 'integrity_score' => ['required', 'integer', 'between:1,5'], 'traceability_score' => ['required', 'integer', 'between:1,5'], 'probability_score' => ['required', 'integer', 'between:1,5'], 'interrupted_services' => ['nullable', 'string'], 'impacted_users' => ['nullable', 'string'], 'impacted_applications' => ['nullable', 'string'], 'fallback_solution' => ['nullable', 'string'], 'rto_minutes' => ['nullable', 'integer', 'min:0'], 'rpo_minutes' => ['nullable', 'integer', 'min:0'], 'has_backup' => ['nullable', 'boolean'], 'has_redundancy' => ['nullable', 'boolean'], 'single_point_of_failure' => ['nullable', 'boolean'], 'obsolete' => ['nullable', 'boolean'], 'visibility' => ['required', Rule::in(['owner', 'participants', 'institutional'])], 'participant_ids' => ['array'], 'participant_ids.*' => ['exists:departments,id'], 'process_ids' => ['array'], 'process_ids.*' => ['exists:institutional_processes,id']];
    }

    private function score(array &$d): array
    {
        foreach (['has_backup', 'has_redundancy', 'single_point_of_failure', 'obsolete'] as $f) {
            $d[$f] = request()->boolean($f);
        }$impact = max($d['availability_score'], $d['confidentiality_score'], $d['integrity_score'], $d['traceability_score']);
        $score = $impact * $d['probability_score'];
        $level = $score <= 4 ? 'low' : ($score <= 9 ? 'medium' : ($score <= 16 ? 'high' : 'critical'));

        return [$score, $level];
    }

    private function guard(Request $r, string $role = 'viewer'): void
    {
        abort_unless($this->access->allows($r->user(), $role), 403);
    }

    private function edit(Request $r, InstitutionalAsset $a): void
    {
        $this->guard($r, 'contributor');
        abort_unless($r->user()->isInstitutionalSuperAdmin() || (int) $a->owner_department_id === (int) $r->user()->department_id || $a->participatingDepartments()->whereKey($r->user()->department_id)->exists(), 403);
    }

    private function history(InstitutionalAsset $a, string $e, User $u, ?string $c = null): void
    {
        InstitutionalAssetHistory::create(['institutional_asset_id' => $a->id, 'event_type' => $e, 'comment' => $c, 'actor_id' => $u->id, 'occurred_at' => now()]);
    }
}
