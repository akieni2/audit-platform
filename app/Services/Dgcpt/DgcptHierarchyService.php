<?php

namespace App\Services\Dgcpt;

use App\Models\Dgcpt\AuditDomain;
use App\Models\Dgcpt\AuditTemplate;
use App\Models\Dgcpt\TreasuryEntity;
use App\Models\Dgcpt\TreasuryService;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Support\Collection;

final class DgcptHierarchyService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function tree(?User $user = null): Collection
    {
        $query = TreasuryEntity::query()
            ->with(['children.children', 'treasuryServices'])
            ->whereNull('parent_entity_id')
            ->orderBy('name');

        if ($user !== null) {
            $query->visibleToUser($user);
        }

        return $query->get()->map(fn (TreasuryEntity $entity) => $this->node($entity));
    }

    /**
     * @return array<string, mixed>
     */
    public function node(TreasuryEntity $entity): array
    {
        $entity->loadMissing(['children.treasuryServices', 'treasuryServices']);

        return [
            'id' => $entity->id,
            'code' => $entity->code,
            'name' => $entity->name,
            'entity_type' => $entity->entity_type,
            'entity_type_label' => $entity->entityTypeLabel(),
            'province' => $entity->province,
            'country' => $entity->country,
            'services' => $entity->treasuryServices->map(fn (TreasuryService $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'service_type' => $s->service_type,
            ])->values()->all(),
            'children' => $entity->children->map(fn (TreasuryEntity $child) => $this->node($child))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function missionContext(Mission $mission): array
    {
        $mission->loadMissing([
            'treasuryEntity.parent',
            'treasuryService',
            'auditDomain',
            'auditTemplate.questionnaireTemplate',
            'auditTemplate.workflowTemplate',
        ]);

        return [
            'entity' => $mission->treasuryEntity ? [
                'id' => $mission->treasuryEntity->id,
                'code' => $mission->treasuryEntity->code,
                'name' => $mission->treasuryEntity->name,
                'type' => $mission->treasuryEntity->entity_type,
                'province' => $mission->treasuryEntity->province,
            ] : null,
            'service' => $mission->treasuryService ? [
                'id' => $mission->treasuryService->id,
                'code' => $mission->treasuryService->code,
                'name' => $mission->treasuryService->name,
            ] : null,
            'domain' => $mission->auditDomain ? [
                'id' => $mission->auditDomain->id,
                'code' => $mission->auditDomain->code,
                'name' => $mission->auditDomain->name,
            ] : null,
            'template' => $mission->auditTemplate ? [
                'id' => $mission->auditTemplate->id,
                'code' => $mission->auditTemplate->code,
                'name' => $mission->auditTemplate->name,
            ] : null,
        ];
    }

    /**
     * @return Collection<int, AuditDomain>
     */
    public function activeDomains(): Collection
    {
        return AuditDomain::query()->active()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, AuditTemplate>
     */
    public function activeTemplates(): Collection
    {
        return AuditTemplate::query()
            ->active()
            ->with(['auditDomain', 'questionnaireTemplate', 'workflowTemplate', 'formTemplate'])
            ->orderBy('name')
            ->get();
    }
}
