<?php

namespace App\Services\Workflow\Components;

use App\Models\WorkflowStage;
use App\Services\Workflow\Components\Contracts\WorkflowStageComponent;
use InvalidArgumentException;

class WorkflowStageComponentRegistry
{
    /**
     * @var array<string, WorkflowStageComponent>
     */
    private array $components = [];

    /**
     * @param  iterable<WorkflowStageComponent>  $components
     */
    public function __construct(iterable $components)
    {
        foreach ($components as $component) {
            $keys = array_unique([$component->key(), ...$component->aliases()]);
            foreach ($keys as $key) {
                $this->components[$key] = $component;
            }
        }
    }

    public function resolve(WorkflowStage $stage): WorkflowStageComponent
    {
        $key = $stage->resolvedComponentKey();

        if (! isset($this->components[$key])) {
            throw new InvalidArgumentException(sprintf(
                'Aucun composant workflow n’est enregistré pour la clé "%s".',
                $key
            ));
        }

        return $this->components[$key];
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_values(array_unique(array_keys($this->components)));
    }
}
