<?php

namespace App\Http\Controllers;

use App\Models\{CfdtAttempt, CfdtCertificate, CfdtCourse, CfdtEnrollment, Department, User};
use App\Notifications\Cfdt\CfdtTestAssignedNotification;
use App\Support\UserRoles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CfdtController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $user = $request->user();
        $courses = CfdtCourse::query()
            ->when(! $this->canReview($user) && $this->canCreate($user), fn ($query) => $query->where('created_by', $user->id))
            ->when(! $this->canReview($user) && ! $this->canCreate($user), fn ($query) => $query->where('status', 'published'))
            ->withCount('enrollments')->latest()->get();
        $enrollments = CfdtEnrollment::with(['course', 'certificate', 'attempts'])->where('user_id', $user->id)->latest('assigned_at')->get();
        $completed = $enrollments->where('status', 'completed')->count();
        $average = $enrollments->flatMap->attempts->avg('percentage');

        return view('cfdt.index', compact('courses', 'enrollments', 'completed', 'average'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canCreate($request->user()), 403);
        $data = $request->validate([
            'code' => 'required|max:50|unique:cfdt_courses', 'title' => 'required|max:255', 'description' => 'nullable',
            'duration_minutes' => 'nullable|integer|min:1', 'pass_mark' => 'required|integer|between:1,100',
            'max_attempts' => 'required|integer|between:1,10', 'content' => 'nullable',
        ]);
        $course = CfdtCourse::create([...$data, 'content' => [['title' => 'Support principal', 'body' => $request->input('content')]], 'questions' => [], 'created_by' => $request->user()->id]);

        return redirect()->route('cfdt.show', $course);
    }

    public function show(Request $request, CfdtCourse $course)
    {
        $this->guard($request);
        $user = $request->user();
        $enrollment = CfdtEnrollment::with(['attempts', 'certificate'])->where(['course_id' => $course->id, 'user_id' => $user->id])->first();
        abort_unless($course->created_by === $user->id || $this->canReview($user) || ($course->status === 'published' && $enrollment), 403);
        $canEdit = $course->created_by === $user->id && in_array($course->status, ['draft', 'changes_requested'], true);
        $canReview = $this->canReview($user);
        $canAssign = $course->status === 'published' && ($course->created_by === $user->id || $canReview);
        $users = $canAssign ? User::with('department')->where('active', true)->orderBy('name')->orderBy('prenom')->get() : collect();
        $departments = $canAssign ? Department::where('active', true)->orderBy('name')->get() : collect();
        $roleCategories = collect(UserRoles::all())->mapWithKeys(fn ($role) => [$role => UserRoles::label($role)]);

        return view('cfdt.show', compact('course', 'enrollment', 'users', 'departments', 'roleCategories', 'canEdit', 'canReview', 'canAssign'));
    }

    public function question(Request $request, CfdtCourse $course)
    {
        abort_unless($course->created_by === $request->user()->id && in_array($course->status, ['draft', 'changes_requested'], true), 403);
        $data = $request->validate(['text' => 'required', 'type' => ['required', Rule::in(['single', 'multiple'])], 'options' => 'required|array|min:2', 'correct' => 'required|array|min:1', 'explanation' => 'nullable', 'points' => 'required|integer|min:1']);
        $questions = $course->questions ?? [];
        $questions[] = [...$data, 'id' => (string) Str::uuid()];
        $course->update(['questions' => $questions]);

        return back()->with('status', 'Question ajoutée.');
    }

    public function submitForReview(Request $request, CfdtCourse $course)
    {
        abort_unless($course->created_by === $request->user()->id && in_array($course->status, ['draft', 'changes_requested'], true), 403);
        if (empty($course->questions)) throw ValidationException::withMessages(['questions' => 'Ajoutez au moins une question avant de soumettre le QCM.']);
        $course->update(['status' => 'pending_review', 'review_observation' => null, 'submitted_at' => now(), 'reviewed_at' => null, 'validated_by' => null]);

        return back()->with('status', 'Le QCM a été transmis au superviseur pour validation.');
    }

    public function review(Request $request, CfdtCourse $course)
    {
        abort_unless($this->canReview($request->user()) && $course->status === 'pending_review', 403);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'return'])], 'review_observation' => 'nullable|string|max:5000']);
        if ($data['decision'] === 'return' && blank($data['review_observation'] ?? null)) throw ValidationException::withMessages(['review_observation' => 'Expliquez les raisons du renvoi au formateur.']);
        $approved = $data['decision'] === 'approve';
        $course->update(['status' => $approved ? 'published' : 'changes_requested', 'review_observation' => $data['review_observation'] ?? null, 'validated_by' => $request->user()->id, 'reviewed_at' => now(), 'published_at' => $approved ? now() : null]);

        return back()->with('status', $approved ? 'QCM validé et publié pour les apprenants.' : 'QCM renvoyé au formateur avec vos observations.');
    }

    public function publish(Request $request, CfdtCourse $course)
    {
        $request->merge(['decision' => 'approve']);

        return $this->review($request, $course);
    }

    public function enroll(Request $request, CfdtCourse $course)
    {
        abort_unless($course->status === 'published' && ($course->created_by === $request->user()->id || $this->canReview($request->user())), 403);
        $data = $request->validate(['user_ids' => 'nullable|array', 'user_ids.*' => 'exists:users,id', 'department_ids' => 'nullable|array', 'department_ids.*' => 'exists:departments,id', 'role_categories' => 'nullable|array', 'role_categories.*' => ['string', Rule::in(UserRoles::all())], 'include_descendants' => 'nullable|boolean', 'expires_at' => 'required|date|after:now']);
        if (empty($data['user_ids']) && empty($data['department_ids']) && empty($data['role_categories'])) throw ValidationException::withMessages(['audience' => 'Sélectionnez au moins un agent, une structure ou une fonction.']);
        $departmentIds = array_map('intval', $data['department_ids'] ?? []);
        if ($request->boolean('include_descendants')) $departmentIds = collect($departmentIds)->flatMap(fn ($id) => Department::subtreeIds($id))->unique()->values()->all();
        $targets = User::query()->where('active', true)->where(function ($query) use ($data, $departmentIds) {
            $first = true;
            foreach ([['id', $data['user_ids'] ?? []], ['department_id', $departmentIds], ['role', $data['role_categories'] ?? []]] as [$column, $values]) {
                if (empty($values)) continue;
                $first ? $query->whereIn($column, $values) : $query->orWhereIn($column, $values);
                $first = false;
            }
        })->get();
        if ($targets->isEmpty()) throw ValidationException::withMessages(['audience' => 'Aucun agent actif ne correspond aux critères sélectionnés.']);
        foreach ($targets as $user) {
            if ($user->cfdt_role === null) $user->update(['cfdt_role' => 'learner']);
            $enrollment = CfdtEnrollment::updateOrCreate(['course_id' => $course->id, 'user_id' => $user->id], ['assigned_by' => $request->user()->id, 'assigned_at' => now(), 'expires_at' => $data['expires_at'], 'invitation_token' => (string) Str::uuid(), 'notified_at' => now(), 'assignment_source' => $this->assignmentSource($data), 'assignment_context' => ['departments' => $data['department_ids'] ?? [], 'roles' => $data['role_categories'] ?? []]]);
            $enrollment->load('course');
            $user->notify(new CfdtTestAssignedNotification($enrollment));
            try { $user->notify(new CfdtTestAssignedNotification($enrollment, true)); } catch (\Throwable $exception) { Log::warning('Échec invitation test CFDT par courriel.', ['enrollment_id' => $enrollment->id, 'error' => $exception->getMessage()]); }
        }

        return back()->with('status', $targets->count().' agent(s) affecté(s). Les notifications internes ont été créées.');
    }

    public function invitation(Request $request, string $token) { $e = CfdtEnrollment::with('course')->where('invitation_token', $token)->where('user_id', $request->user()->id)->firstOrFail(); abort_if($e->isExpired(), 403, 'Ce lien d’invitation a expiré.'); return redirect()->route('cfdt.show', $e->course); }
    public function attempt(Request $request, CfdtCourse $course) { $this->guard($request); abort_unless($course->status === 'published', 403); $e = CfdtEnrollment::where(['course_id' => $course->id, 'user_id' => $request->user()->id])->firstOrFail(); abort_if($e->isExpired(), 403, 'La date limite de ce test est dépassée.'); abort_if($e->attempts()->count() >= $course->max_attempts, 403, 'Nombre maximal de tentatives atteint.'); return view('cfdt.attempt', compact('course', 'e')); }
    public function submit(Request $request, CfdtCourse $course) { $this->guard($request); abort_unless($course->status === 'published', 403); $e = CfdtEnrollment::where(['course_id' => $course->id, 'user_id' => $request->user()->id])->firstOrFail(); abort_if($e->isExpired(), 403, 'La date limite de ce test est dépassée.'); abort_if($e->attempts()->count() >= $course->max_attempts, 403); $answers = $request->input('answers', []); $score = $total = 0; foreach ($course->questions as $q) { $pts = (int) $q['points']; $total += $pts; $given = (array) ($answers[$q['id']] ?? []); sort($given); $correct = (array) $q['correct']; sort($correct); if ($given === $correct) $score += $pts; } $pct = $total ? round($score * 100 / $total, 2) : 0; $passed = $pct >= $course->pass_mark; DB::transaction(function () use ($e, $course, $answers, $score, $total, $pct, $passed) { CfdtAttempt::create(['enrollment_id' => $e->id, 'question_snapshot' => $course->questions, 'answers' => $answers, 'score' => $score, 'total' => $total, 'percentage' => $pct, 'passed' => $passed, 'started_at' => now(), 'submitted_at' => now()]); if ($passed) { $e->update(['status' => 'completed', 'progress' => 100, 'completed_at' => now()]); $token = (string) Str::uuid(); CfdtCertificate::firstOrCreate(['enrollment_id' => $e->id], ['verification_token' => $token, 'number' => 'CFDT-'.now()->format('Y').'-'.str_pad($e->id, 6, '0', STR_PAD_LEFT), 'score' => $pct, 'issued_at' => now(), 'signature_hash' => hash('sha256', $token.'|'.$e->id.'|'.$pct)]); } }); return redirect()->route('cfdt.show', $course)->with('status', $passed ? 'Réussite : certificat délivré.' : 'Seuil non atteint.'); }
    public function certificate(Request $request, CfdtCertificate $certificate) { $certificate->load('enrollment.user', 'enrollment.course'); abort_unless($certificate->enrollment->user_id === $request->user()->id || $request->user()->canManageCfdt(), 403); return Pdf::loadView('cfdt.certificate', compact('certificate'))->download($certificate->number.'.pdf'); }
    public function verify(string $token) { $certificate = CfdtCertificate::with('enrollment.user', 'enrollment.course')->where('verification_token', $token)->firstOrFail(); $expected = hash('sha256', $certificate->verification_token.'|'.$certificate->enrollment_id.'|'.$certificate->score); $valid = hash_equals($expected, $certificate->signature_hash); return view('cfdt.verify', compact('certificate', 'valid')); }
    private function guard(Request $request): void { abort_unless($request->user()->canAccessCfdt(), 403); }
    private function canCreate(User $user): bool { return $user->isInstitutionalSuperAdmin() || in_array($user->cfdt_role, ['trainer', 'administrator'], true); }
    private function canReview(User $user): bool { return $user->isInstitutionalSuperAdmin() || in_array($user->cfdt_role, ['validator', 'administrator'], true); }
    private function assignmentSource(array $data): string { return ! empty($data['department_ids']) ? 'structure' : (! empty($data['role_categories']) ? 'fonction' : 'individuel'); }
}
