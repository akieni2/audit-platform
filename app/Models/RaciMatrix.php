<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaciMatrix extends Model
{
    protected $fillable = [
        'raci_template_id',
        'mission_id',
        'department_id',
        'workflow_instance_id',
        'name',
        'process_label',
        'status',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function raciTemplate(): BelongsTo
    {
        return $this->belongsTo(RaciTemplate::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RaciAssignment::class)->orderBy('process_sort_order')->orderBy('id');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(RaciValidation::class)->orderByDesc('id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(RaciSnapshot::class)->orderByDesc('id');
    }
}
