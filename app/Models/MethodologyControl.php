<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MethodologyControl extends Model
{
    protected $fillable = [
        'methodology_template_id',
        'methodology_category_id',
        'control_reference',
        'title',
        'description',
        'control_type',
        'criticality',
        'default_workflow_stage_code',
        'control_objective',
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

    public function requirements(): HasMany
    {
        return $this->hasMany(MethodologyRequirement::class)->orderBy('requirement_reference');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(MethodologyMapping::class)->orderByDesc('id');
    }

    public function controlMeasures(): HasMany
    {
        return $this->hasMany(ControlMeasure::class)->orderBy('code');
    }
}
