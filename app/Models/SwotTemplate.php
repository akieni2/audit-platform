<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SwotTemplate extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'department_id',
        'name',
        'slug',
        'code',
        'description',
        'analysis_scope',
        'active',
        'is_global',
        'version',
        'lifecycle_status',
        'weighting_profile',
        'metadata',
        'signature_hash',
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
            'is_global' => 'boolean',
            'version' => 'integer',
            'weighting_profile' => 'array',
            'metadata' => 'array',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function lifecycleLabel(): string
    {
        return match ((string) ($this->lifecycle_status ?? self::STATUS_DRAFT)) {
            self::STATUS_PUBLISHED => 'Publie',
            self::STATUS_ARCHIVED => 'Archive',
            default => 'Brouillon',
        };
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_template_id')->withTrashed();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(SwotCategory::class)->orderBy('sort_order')->orderBy('id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SwotEntry::class)->orderBy('sort_order')->orderBy('id');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(SwotAnalysis::class)->orderByDesc('id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SwotSnapshot::class)->orderByDesc('id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(SwotRecommendation::class)->orderByDesc('priority_index')->orderByDesc('id');
    }
}
