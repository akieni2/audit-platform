<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MethodologyCategory extends Model
{
    protected $fillable = [
        'methodology_template_id',
        'parent_id',
        'name',
        'code',
        'description',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function methodologyTemplate(): BelongsTo
    {
        return $this->belongsTo(MethodologyTemplate::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function controls(): HasMany
    {
        return $this->hasMany(MethodologyControl::class)->orderBy('control_reference');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(MethodologyRequirement::class)->orderBy('requirement_reference');
    }
}
