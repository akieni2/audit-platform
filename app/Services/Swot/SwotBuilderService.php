<?php

namespace App\Services\Swot;

use App\Models\SwotTemplate;
use App\Models\User;
use Illuminate\Support\Collection;

class SwotBuilderService
{
    public function __construct(
        private SwotScoringService $scoring,
    ) {}

    /**
     * @return Collection<int, SwotTemplate>
     */
    public function library(?User $actor = null): Collection
    {
        return SwotTemplate::query()
            ->when(
                $actor && ! $actor->canViewAllInstitutionalData() && $actor->department_id !== null,
                fn ($query) => $query->where(function ($inner) use ($actor) {
                    $inner->whereNull('department_id')
                        ->orWhere('department_id', $actor->department_id)
                        ->orWhere('is_global', true);
                })
            )
            ->withCount(['categories', 'entries', 'analyses'])
            ->with('department')
            ->latest('updated_at')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function editorPayload(SwotTemplate $template): array
    {
        $template->loadMissing(['department', 'categories.entries', 'entries.swotCategory']);
        $summary = $this->scoring->summarizeTemplate($template);

        return [
            'template' => $template,
            'summary' => $summary,
            'matrix' => collect($summary['quadrants'])
                ->map(fn (array $quadrant, string $key) => [
                    'key' => $key,
                    ...$quadrant,
                ])->values(),
        ];
    }
}
