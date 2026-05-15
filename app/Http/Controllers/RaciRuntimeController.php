<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\RaciAssignment;
use App\Models\RaciRole;
use App\Models\RaciTemplate;
use App\Models\User;
use App\Services\Raci\RaciAnalyticsService;
use App\Services\Raci\RaciAssignmentService;
use App\Services\Raci\RaciValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RaciRuntimeController extends Controller
{
    public function __construct(
        private RaciAssignmentService $assignments,
        private RaciAnalyticsService $analytics,
        private RaciValidationService $validations,
    ) {}

    public function show(Request $request, Mission $mission): View
    {
        $this->authorize('view', $mission);

        return view('raci.runtime.show', [
            'mission' => $mission,
            'raciView' => $this->analytics->missionSnapshot($mission),
            'raciTemplates' => RaciTemplate::query()
                ->where(function ($query) use ($mission) {
                    $query->whereNull('department_id')
                        ->orWhere('department_id', $mission->department_id)
                        ->orWhere('is_global', true);
                })
                ->where('active', true)
                ->orderBy('name')
                ->get(),
            'roleOptions' => RaciRole::query()
                ->whereHas('raciTemplate', function ($query) use ($mission) {
                    $query->whereNull('department_id')
                        ->orWhere('department_id', $mission->department_id)
                        ->orWhere('is_global', true);
                })
                ->orderBy('name')
                ->get(),
            'userOptions' => User::query()
                ->where('active', true)
                ->when($mission->department_id !== null, fn ($query) => $query->where('department_id', $mission->department_id))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function assignments(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('view', $mission);

        $validated = $request->validate([
            'raci_template_id' => ['required', 'exists:raci_templates,id'],
            'raci_role_id' => ['required', 'exists:raci_roles,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'process_label' => ['required', 'string', 'max:255'],
            'role_type' => ['required', 'string', 'max:60'],
            'responsibility_level' => ['required', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
        ]);

        $template = RaciTemplate::query()->findOrFail($validated['raci_template_id']);
        $this->assignments->assignForMission($template, $mission, [
            'status' => 'assigned',
            'process_label' => $validated['process_label'],
            'assignments' => [[
                'raci_role_id' => $validated['raci_role_id'],
                'assigned_user_id' => $validated['assigned_user_id'] ?? null,
                'process_label' => $validated['process_label'],
                'role_type' => $validated['role_type'],
                'responsibility_level' => $validated['responsibility_level'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'assigned',
            ]],
        ], $request->user());

        return redirect()->route('raci.show', $mission)->with('status', 'Affectation RACI enregistree.');
    }

    public function validation(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('view', $mission);

        $validated = $request->validate([
            'raci_assignment_id' => ['required', 'exists:raci_assignments,id'],
            'status' => ['required', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
        ]);

        $assignment = RaciAssignment::query()->findOrFail($validated['raci_assignment_id']);
        $this->validations->record($assignment, $validated, $request->user());

        return redirect()->route('raci.show', $mission)->with('status', 'Validation RACI enregistree.');
    }

    public function analytics(Request $request, Mission $mission): View
    {
        $this->authorize('view', $mission);

        return view('raci.runtime.analytics', [
            'mission' => $mission,
            'raciView' => $this->analytics->missionSnapshot($mission),
        ]);
    }
}
