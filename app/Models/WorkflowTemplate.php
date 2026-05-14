<?php

namespace App\Models;

use App\Domain\Workflow\Enums\WorkflowTemplateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowTemplate extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DEPRECATED = 'deprecated';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'department_id',
        'methodology_template_id',
        'name',
        'slug',
        'description',
        'code',
        'active',
        'is_system',
        'version',
        'status',
        'visibility_scope',
        'sharing_mode',
        'is_global_template',
        'is_private_template',
        'governance_tags',
        'signature_hash',
        'deprecated_at',
        'source_template_id',
        'created_by',
        'updated_by',
        'published_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_system' => 'boolean',
            'version' => 'integer',
            'is_global_template' => 'boolean',
            'is_private_template' => 'boolean',
            'governance_tags' => 'array',

            // IMPORTANT : enum cast Laravel natif
            'status' => WorkflowTemplateStatus::class,

            'source_template_id' => 'integer',

            'published_at' => 'datetime',
            'deprecated_at' => 'datetime',
            'archived_at' => 'datetime',

            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function lifecycleLabel(): string
    {
        $status = $this->status instanceof WorkflowTemplateStatus
            ? $this->status
            : WorkflowTemplateStatus::tryFrom((string) $this->status);

        return $status?->label() ?? (string) $this->status;
    }

    public function isImmutable(): bool
    {
        $status = $this->status instanceof WorkflowTemplateStatus
            ? $this->status
            : WorkflowTemplateStatus::tryFrom((string) $this->status);

        return $status === WorkflowTemplateStatus::Published;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function methodologyTemplate(): BelongsTo
    {
        return $this->belongsTo(MethodologyTemplate::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(WorkflowStage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class);
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_template_id')
            ->withTrashed();
    }

    public function derivedVersions(): HasMany
    {
        return $this->hasMany(self::class, 'source_template_id')
            ->orderBy('version');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')
            ->withTrashed();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')
            ->withTrashed();
    }

    public function isVisibleToDepartment(?int $departmentId, ?array $departmentIds = null): bool
    {
        if ($this->is_global_template || $this->department_id === null) {
            return true;
        }

        if ($departmentId !== null && (int) $this->department_id === (int) $departmentId) {
            return true;
        }

        if ($departmentIds !== null) {
            foreach ($departmentIds as $id) {
                if ((int) $this->department_id === (int) $id) {
                    return true;
                }
            }
        }

        return false;
    }
}