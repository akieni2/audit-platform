<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MethodologyTemplate extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_DEPRECATED = 'deprecated';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'department_id',
        'default_workflow_template_id',
        'name',
        'slug',
        'framework_key',
        'code',
        'description',
        'active',
        'is_system',
        'is_global',
        'version',
        'lifecycle_status',
        'department_scope',
        'metadata',
        'signature_hash',
        'source_template_id',
        'created_by',
        'updated_by',
        'published_at',
        'deprecated_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_system' => 'boolean',
            'is_global' => 'boolean',
            'version' => 'integer',
            'department_scope' => 'array',
            'metadata' => 'array',
            'published_at' => 'datetime',
            'deprecated_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function lifecycleOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PUBLISHED => 'Publié',
            self::STATUS_DEPRECATED => 'Déprécié',
            self::STATUS_ARCHIVED => 'Archivé',
        ];
    }

    public function lifecycleLabel(): string
    {
        return self::lifecycleOptions()[$this->lifecycle_status ?? self::STATUS_DRAFT] ?? (string) $this->lifecycle_status;
    }

    public function isImmutable(): bool
    {
        return $this->lifecycle_status === self::STATUS_PUBLISHED;
    }

    public function isVisibleToDepartment(?int $departmentId, ?array $departmentIds = null): bool
    {
        if ($this->is_global) {
            return true;
        }

        $scope = $this->department_scope;
        if ($scope === null || $scope === []) {
            return $this->department_id === null || (int) $this->department_id === (int) $departmentId;
        }

        $ids = array_map('intval', $scope);
        if ($departmentId !== null && in_array((int) $departmentId, $ids, true)) {
            return true;
        }

        if ($departmentIds !== null) {
            foreach ($departmentIds as $id) {
                if (in_array((int) $id, $ids, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function defaultWorkflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'default_workflow_template_id');
    }

    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_template_id')->withTrashed();
    }

    public function derivedVersions(): HasMany
    {
        return $this->hasMany(self::class, 'source_template_id')->orderBy('version');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(MethodologyCategory::class)->orderBy('sort_order')->orderBy('id');
    }

    public function controls(): HasMany
    {
        return $this->hasMany(MethodologyControl::class)->orderBy('control_reference');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(MethodologyRequirement::class)->orderBy('requirement_reference');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(MethodologyMapping::class)->orderByDesc('id');
    }

    public function controlLibraries(): HasMany
    {
        return $this->hasMany(ControlLibrary::class)->orderBy('name');
    }

    public function workflowTemplates(): HasMany
    {
        return $this->hasMany(WorkflowTemplate::class)->orderBy('name');
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
