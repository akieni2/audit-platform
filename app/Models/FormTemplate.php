<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormTemplate extends Model
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
        'component_key',
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

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->orderByDesc('submitted_at')->orderByDesc('id');
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
