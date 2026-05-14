<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TaxonomyMapping extends Model
{
    protected $fillable = [
        'taxonomy_id',
        'taxonomy_term_id',
        'department_id',
        'mappable_type',
        'mappable_id',
        'mapping_type',
        'external_reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }

    public function taxonomyTerm(): BelongsTo
    {
        return $this->belongsTo(TaxonomyTerm::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function mappable(): MorphTo
    {
        return $this->morphTo();
    }
}
