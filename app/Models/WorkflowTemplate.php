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
        'name',
        'slug',
        'description',
        'code',
        'active',
        'is_system',
        'version',
        'status',
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
            'status' => fn ($value) => WorkflowTemplateStatus::tryFrom((string) $value),
            'source_template_id' => 'integer',
            'published_at' => 'datetime',
            'deprecated_at' => 'datetime',
            'archived_at' => 'datetime',
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

    public function stages(): HasMany
    {
        return $this->hasMany(WorkflowStage::class)->orderBy('sort_order')->orderBy('id');
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
        return $this->belongsTo(self::class, 'source_template_id')->withTrashed();
    }

    public function derivedVersions(): HasMany
    {
        return $this->hasMany(self::class, 'source_template_id')->orderBy('version');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
