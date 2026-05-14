<?php

namespace App\Services\Runtime;

use App\Models\ControlMeasure;
use App\Models\Mission;
use App\Models\Taxonomy;
use App\Models\WorkflowStage;
use App\Services\Taxonomy\TaxonomyEngineService;
use Illuminate\Support\Str;

class RiskSuggestionService
{
    public function __construct(
        private TaxonomyEngineService $taxonomies,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function suggestForMission(Mission $mission, ?WorkflowStage $stage = null, array $context = []): array
    {
        $mission->loadMissing(['department', 'workflowInstance']);

        $category = Str::headline(str_replace('_', ' ', (string) ($context['risk_category'] ?? $stage?->code ?? 'general')));
        $riskTaxonomy = Taxonomy::query()
            ->where('taxonomy_type', 'risk')
            ->where(function ($query) use ($mission) {
                $query->where('is_national', true)
                    ->orWhereNull('department_id')
                    ->orWhere('department_id', $mission->department_id);
            })
            ->orderByDesc('is_national')
            ->first();

        $term = $riskTaxonomy ? $this->taxonomies->resolveAlias($riskTaxonomy, $category) : null;
        $controls = ControlMeasure::query()
            ->with(['controlLibrary', 'methodologyControl'])
            ->when($term !== null, fn ($query) => $query->where('taxonomy_term_id', $term->id))
            ->orWhere('code', 'like', '%'.Str::upper(Str::substr($category, 0, 3)).'%')
            ->limit(5)
            ->get();

        return [
            'risk_category' => $category,
            'taxonomy_term' => $term,
            'suggested_controls' => $controls,
            'recommendations' => $controls->map(fn (ControlMeasure $measure) => [
                'code' => $measure->code,
                'title' => $measure->title,
                'library' => $measure->controlLibrary?->name,
                'methodology_control' => $measure->methodologyControl?->control_reference,
            ])->all(),
        ];
    }
}
