<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'active',
        'supervisor_user_id',
        'parent_department_id',
        'governance_scope',
        'default_methodology_template_id',
        'default_taxonomy_id',
        'executive_visibility',
        'intelligence_profile',
        'accent_color',
        'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'executive_visibility' => 'boolean',
            'intelligence_profile' => 'array',
        ];
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id')->withTrashed();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_department_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_department_id')->orderBy('code');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    public function defaultMethodologyTemplate(): BelongsTo
    {
        return $this->belongsTo(MethodologyTemplate::class, 'default_methodology_template_id');
    }

    public function defaultTaxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'default_taxonomy_id');
    }

    public function swotTemplates(): HasMany
    {
        return $this->hasMany(SwotTemplate::class)->orderBy('name');
    }

    public function swotAnalyses(): HasMany
    {
        return $this->hasMany(SwotAnalysis::class)->orderByDesc('id');
    }

    public function swotRecommendations(): HasMany
    {
        return $this->hasMany(SwotRecommendation::class)->orderByDesc('id');
    }
}
