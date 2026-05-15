<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RaciTemplate extends Model
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
            'metadata' => 'array',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_template_id')->withTrashed();
    }

    public function matrices(): HasMany
    {
        return $this->hasMany(RaciMatrix::class)->orderByDesc('id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(RaciRole::class)->orderBy('sort_order')->orderBy('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RaciAssignment::class)->orderBy('process_sort_order')->orderBy('id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(RaciSnapshot::class)->orderByDesc('id');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(RaciValidation::class)->orderByDesc('id');
    }
}
