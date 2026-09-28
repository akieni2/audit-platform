<?php

namespace App\Http\Controllers\Iam\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\MobileMenuAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\MobileMenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MobileMenuAccessController extends Controller
{
    public function index(MobileMenuService $mobileMenus): View
    {
        $users = User::query()->where('active', true)->orderBy('name')->orderBy('prenom')->get(['id', 'name', 'prenom', 'email']);
        $roles = Role::query()->where('active', true)->orderBy('hierarchy_level')->orderBy('name')->get(['id', 'name', 'slug']);
        $departments = Department::query()->where('active', true)->orderBy('code')->get(['id', 'code', 'name']);

        $assignments = MobileMenuAssignment::query()
            ->active()
            ->orderBy('subject_type')
            ->orderBy('subject_id')
            ->orderBy('menu_key')
            ->get()
            ->groupBy(fn (MobileMenuAssignment $assignment) => $assignment->subject_type.':'.$assignment->subject_id);

        return view('iam.admin.mobile-menu.index', [
            'catalog' => $mobileMenus->catalog(),
            'users' => $users,
            'roles' => $roles,
            'departments' => $departments,
            'assignments' => $assignments,
            'subjectLabels' => [
                MobileMenuAssignment::SUBJECT_USER => $users->mapWithKeys(fn (User $user) => [$user->id => $user->displayName().' — '.$user->email]),
                MobileMenuAssignment::SUBJECT_ROLE => $roles->mapWithKeys(fn (Role $role) => [$role->id => $role->name]),
                MobileMenuAssignment::SUBJECT_DEPARTMENT => $departments->mapWithKeys(fn (Department $department) => [$department->id => $department->code.' — '.$department->name]),
            ],
        ]);
    }

    public function store(Request $request, MobileMenuService $mobileMenus): RedirectResponse
    {
        $catalogKeys = array_keys($mobileMenus->catalog());
        $validated = $request->validate([
            'subject_type' => ['required', Rule::in([
                MobileMenuAssignment::SUBJECT_USER,
                MobileMenuAssignment::SUBJECT_ROLE,
                MobileMenuAssignment::SUBJECT_DEPARTMENT,
            ])],
            'subject_id' => ['required', 'integer', 'min:1'],
            'menus' => ['nullable', 'array'],
            'menus.*' => ['string', Rule::in($catalogKeys)],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $this->assertSubjectExists($validated['subject_type'], (int) $validated['subject_id']);
        $menuKeys = array_values(array_unique($validated['menus'] ?? []));

        DB::transaction(function () use ($request, $validated, $menuKeys): void {
            MobileMenuAssignment::query()
                ->where('subject_type', $validated['subject_type'])
                ->where('subject_id', $validated['subject_id'])
                ->delete();

            foreach ($menuKeys as $menuKey) {
                MobileMenuAssignment::query()->create([
                    'subject_type' => $validated['subject_type'],
                    'subject_id' => $validated['subject_id'],
                    'menu_key' => $menuKey,
                    'granted_by' => $request->user()->id,
                    'expires_at' => $validated['expires_at'] ?? null,
                ]);
            }
        });

        return back()->with('status', 'Les habilitations de l’application mobile ont été mises à jour.');
    }

    public function destroy(string $subjectType, int $subjectId): RedirectResponse
    {
        abort_unless(in_array($subjectType, [
            MobileMenuAssignment::SUBJECT_USER,
            MobileMenuAssignment::SUBJECT_ROLE,
            MobileMenuAssignment::SUBJECT_DEPARTMENT,
        ], true), 404);

        MobileMenuAssignment::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->delete();

        return back()->with('status', 'L’habilitation mobile a été supprimée.');
    }

    private function assertSubjectExists(string $type, int $id): void
    {
        $exists = match ($type) {
            MobileMenuAssignment::SUBJECT_USER => User::query()->whereKey($id)->exists(),
            MobileMenuAssignment::SUBJECT_ROLE => Role::query()->whereKey($id)->exists(),
            MobileMenuAssignment::SUBJECT_DEPARTMENT => Department::query()->whereKey($id)->exists(),
            default => false,
        };

        abort_unless($exists, 422, 'La cible sélectionnée n’existe pas.');
    }
}
