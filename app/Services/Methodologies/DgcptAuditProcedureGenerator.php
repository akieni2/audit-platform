<?php

namespace App\Services\Methodologies;

use App\Models\MethodologyTemplate;
use Illuminate\Support\Collection;

class DgcptAuditProcedureGenerator
{
    /**
     * @return array<string, mixed>
     */
    public function generate(MethodologyTemplate $template): array
    {
        $template->loadMissing(['categories.controls.requirements', 'mappings.taxonomyTerm']);

        $metadata = $template->metadata ?? [];
        $procedure = collect($metadata['audit_procedure'] ?? [])
            ->whenEmpty(fn (Collection $items) => $this->fromCategories($template))
            ->values();

        return [
            'referential' => [
                'id' => $template->id,
                'name' => $template->name,
                'code' => $template->code,
                'framework_key' => $template->framework_key,
                'description' => $template->description,
                'scope' => $metadata['scope'] ?? null,
            ],
            'stages' => $procedure->map(fn (array $stage, int $index) => [
                'rank' => $index + 1,
                'code' => $stage['code'] ?? 'STAGE-'.($index + 1),
                'name' => $stage['name'] ?? 'Etape '.($index + 1),
                'objective' => $stage['objective'] ?? null,
                'expected_outcome' => $stage['expected_outcome'] ?? null,
                'deliverables' => array_values($stage['deliverables'] ?? []),
                'risk_category' => $stage['risk_category'] ?? null,
                'criticality' => $stage['criticality'] ?? null,
                'question_topics' => array_values($stage['question_topics'] ?? []),
                'questions' => $this->questionsForStage($metadata['question_bank'] ?? [], $stage['question_topics'] ?? []),
            ])->all(),
            'deliverables' => collect($metadata['deliverable_library'] ?? [])
                ->whenEmpty(fn () => $procedure->flatMap(fn (array $stage) => $stage['deliverables'] ?? []))
                ->unique()
                ->values()
                ->all(),
            'questions' => array_values($metadata['question_bank'] ?? []),
            'risk_families' => array_values($metadata['risk_families'] ?? []),
            'taxonomy_terms' => $template->mappings
                ->pluck('taxonomyTerm')
                ->filter()
                ->unique('id')
                ->map(fn ($term) => [
                    'code' => $term->code,
                    'name' => $term->name,
                    'description' => $term->description,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fromCategories(MethodologyTemplate $template): Collection
    {
        return $template->categories->map(fn ($category) => [
            'code' => $category->code,
            'name' => $category->name,
            'objective' => $category->description,
            'expected_outcome' => $category->metadata['expected_outcome'] ?? null,
            'deliverables' => $category->metadata['deliverables'] ?? [],
            'risk_category' => null,
            'criticality' => null,
            'question_topics' => [],
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $questions
     * @param  array<int, string>  $topics
     * @return array<int, array<string, string>>
     */
    private function questionsForStage(array $questions, array $topics): array
    {
        if ($topics === []) {
            return [];
        }

        return collect($questions)
            ->filter(fn (array $question) => in_array($question['topic'] ?? '', $topics, true))
            ->values()
            ->all();
    }
}
