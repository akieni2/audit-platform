<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MethodologyRequirement extends Model
{
    protected $fillable = [
        'methodology_template_id',
        'methodology_category_id',
        'methodology_control_id',
        'requirement_reference',
        'title',
        'description',
        'status',
        'applicability_scope',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function methodologyTemplate(): BelongsTo
    {
        return $this->belongsTo(MethodologyTemplate::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MethodologyCategory::class, 'methodology_category_id');
    }

    public function control(): BelongsTo
    {
        return $this->belongsTo(MethodologyControl::class, 'methodology_control_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(MethodologyMapping::class)->orderByDesc('id');
    }
}
