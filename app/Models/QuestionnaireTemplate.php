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

    public const REVIEW_DRAFT = 'draft';

    public const REVIEW_IN_REVIEW = 'in_review';

    public const REVIEW_ADOPTED = 'adopted';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'source_document_name',
        'source_document_path',
        'source_document_sha256',
        'mission_type',
        'methodology_template_id',
        'mission_id',
        'department_scope',
        'visibility_scope',
        'sharing_mode',
        'is_global_template',
        'is_private_template',
        'governance_tags',
        'active',
        'version',
        'lifecycle_status',
        'review_status',
        'review_requested_at',
        'adopted_at',
        'adopted_by',
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
            'governance_tags' => 'array',
            'active' => 'boolean',
            'is_global_template' => 'boolean',
            'is_private_template' => 'boolean',
            'version' => 'integer',
            'published_at' => 'datetime',
            'deprecated_at' => 'datetime',
            'archived_at' => 'datetime',
            'review_requested_at' => 'datetime',
            'adopted_at' => 'datetime',
            'source_template_id' => 'integer',
            'mission_id' => 'integer',
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

    public function methodologyTemplate(): BelongsTo
    {
        return $this->belongsTo(MethodologyTemplate::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(QuestionnaireTemplateReview::class);
    }

    public function adopter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adopted_by');
    }

    public function reviewStatusLabel(): string
    {
        return match ($this->review_status) {
            self::REVIEW_IN_REVIEW => 'En relecture collective',
            self::REVIEW_ADOPTED => 'Version finale adoptée',
            default => 'Brouillon collaboratif',
        };
    }

    public function invalidateReviews(): void
    {
        if ($this->mission_id === null || $this->review_status === self::REVIEW_DRAFT) {
            return;
        }

        $this->reviews()->delete();
        $this->forceFill([
            'review_status' => self::REVIEW_DRAFT,
            'review_requested_at' => null,
            'adopted_at' => null,
            'adopted_by' => null,
        ])->saveQuietly();
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
        if ($this->is_global_template) {
            return true;
        }

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

    public function isAvailableForMission(Mission $mission): bool
    {
        return ($this->mission_id === null || (int) $this->mission_id === (int) $mission->id)
            && $this->isVisibleToDepartment($mission->department_id !== null ? (int) $mission->department_id : null);
    }
}
