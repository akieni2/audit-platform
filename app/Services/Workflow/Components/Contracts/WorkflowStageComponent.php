<?php

namespace App\Services\Workflow\Components\Contracts;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use Illuminate\Http\Request;

interface WorkflowStageComponent
{
    public function key(): string;

    /**
     * @return list<string>
     */
    public function aliases(): array;

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array;

    /**
     * @return array<string, mixed>
     */
    public function handleSubmission(Request $request, WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array;
}
