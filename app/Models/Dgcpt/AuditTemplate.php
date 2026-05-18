<?php

namespace App\Models\Dgcpt;

use App\Models\FormTemplate;
use App\Models\Mission;
use App\Models\QuestionnaireTemplate;
use App\Models\WorkflowTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditTemplate extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'audit_domain_id',
        'questionnaire_template_id',
        'workflow_template_id',
        'form_template_id',
        'applicable_entity_types',
        'active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'applicable_entity_types' => 'array',
            'active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function auditDomain(): BelongsTo
    {
        return $this->belongsTo(AuditDomain::class);
    }

    public function questionnaireTemplate(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class);
    }

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    public function supportsEntityType(?string $entityType): bool
    {
        $types = $this->applicable_entity_types;
        if (! is_array($types) || $types === []) {
            return true;
        }

        return $entityType !== null && in_array($entityType, $types, true);
    }

    /**
     * @param  Builder<AuditTemplate>  $query
     * @return Builder<AuditTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
