<?php

namespace App\Http\Controllers;

use App\Models\ControlLibrary;
use App\Models\MethodologyTemplate;
use App\Models\Taxonomy;
use App\Services\Methodologies\DgcptAuditProcedureGenerator;
use App\Services\Governance\DepartmentConsolidationService;
use App\Services\Methodologies\MethodologyWorkflowMappingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnterpriseCatalogController extends Controller
{
    public function __construct(
        private MethodologyWorkflowMappingService $methodologyMappings,
        private DepartmentConsolidationService $consolidation,
        private DgcptAuditProcedureGenerator $procedureGenerator,
    ) {}

    public function methodologies(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        $methodologies = MethodologyTemplate::query()
            ->withCount(['categories', 'controls', 'requirements', 'mappings'])
            ->with(['department', 'defaultWorkflowTemplate'])
            ->latest('updated_at')
            ->get();

        return view('governance.methodologies', [
            'methodologies' => $methodologies,
            'methodologyStacks' => $methodologies
                ->take(6)
                ->mapWithKeys(fn (MethodologyTemplate $template) => [
                    $template->id => $this->methodologyMappings->resolveStack($template, $actor->department_id),
                ]),
            'procedureSummaries' => $methodologies
                ->mapWithKeys(fn (MethodologyTemplate $template) => [
                    $template->id => $this->procedureGenerator->generate($template),
                ]),
        ]);
    }

    public function taxonomies(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        $taxonomies = Taxonomy::query()
            ->withCount(['terms', 'mappings'])
            ->with('department')
            ->orderByDesc('is_national')
            ->orderBy('name')
            ->get();

        return view('governance.taxonomies', compact('taxonomies'));
    }

    public function controls(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        $controlLibraries = ControlLibrary::query()
            ->withCount(['measures', 'mappings'])
            ->with(['department', 'methodologyTemplate'])
            ->latest('updated_at')
            ->get();

        return view('governance.controls', compact('controlLibraries'));
    }

    public function consolidation(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('governance.consolidation', [
            'consolidation' => $this->consolidation->snapshot($actor->canViewAllInstitutionalData() ? null : $actor->department_id),
        ]);
    }
}
