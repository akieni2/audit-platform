<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionnaireTemplate extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DEPRECATED = 'deprecated';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'mission_type',
        'department_scope',
        'active',
        'version',
        'lifecycle_status',
        'signature_hash',
        'published_at',
        'deprecated_at',
        'archived_at',
        'source_template_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'department_scope' => 'array',
            'active' => 'boolean',
            'version' => 'integer',
            'published_at' => 'datetime',
            'deprecated_at' => 'datetime',
            'archived_at' => 'datetime',
            'source_template_id' => 'integer',
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

    public function sections(): HasMany
    {
        return $this->hasMany(QuestionnaireSection::class)->orderBy('sort_order');
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
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function entretiens(): HasMany
    {
        return $this->hasMany(Entretien::class, 'questionnaire_template_id');
    }

    /**
     * Périmètre institutionnel : null ou vide = référentiel national (tous pôles).
     *
     * @param  list<int>|null  $departmentIds
     */
    public function isVisibleToDepartment(?int $departmentId, ?array $departmentIds = null): bool
    {
        $scope = $this->department_scope;
        if ($scope === null || $scope === []) {
            return true;
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
}
