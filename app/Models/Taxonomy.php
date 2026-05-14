<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Taxonomy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'name',
        'slug',
        'taxonomy_type',
        'description',
        'active',
        'is_national',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_national' => 'boolean',
            'metadata' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(TaxonomyTerm::class)->orderBy('sort_order')->orderBy('id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(TaxonomyMapping::class)->orderByDesc('id');
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
