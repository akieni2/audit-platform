<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxonomyTerm extends Model
{
    protected $fillable = [
        'taxonomy_id',
        'parent_id',
        'name',
        'code',
        'description',
        'alias_terms',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'alias_terms' => 'array',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(TaxonomyMapping::class)->orderByDesc('id');
    }

    public function methodologyMappings(): HasMany
    {
        return $this->hasMany(MethodologyMapping::class)->orderByDesc('id');
    }
}
