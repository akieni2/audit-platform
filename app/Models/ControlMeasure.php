<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControlMeasure extends Model
{
    protected $fillable = [
        'control_library_id',
        'methodology_control_id',
        'taxonomy_term_id',
        'department_id',
        'code',
        'title',
        'description',
        'execution_frequency',
        'owner_role',
        'maturity_level',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'maturity_level' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function controlLibrary(): BelongsTo
    {
        return $this->belongsTo(ControlLibrary::class);
    }

    public function methodologyControl(): BelongsTo
    {
        return $this->belongsTo(MethodologyControl::class);
    }

    public function taxonomyTerm(): BelongsTo
    {
        return $this->belongsTo(TaxonomyTerm::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ControlEvidence::class)->orderByDesc('collected_at')->orderByDesc('id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ControlExecution::class)->orderByDesc('executed_at')->orderByDesc('id');
    }

    public function methodologyMappings(): HasMany
    {
        return $this->hasMany(MethodologyMapping::class)->orderByDesc('id');
    }
}
