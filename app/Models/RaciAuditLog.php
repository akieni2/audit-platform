<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaciAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'raci_template_id',
        'raci_matrix_id',
        'raci_assignment_id',
        'mission_id',
        'department_id',
        'workflow_instance_id',
        'actor_id',
        'event_name',
        'status',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function raciTemplate(): BelongsTo
    {
        return $this->belongsTo(RaciTemplate::class);
    }

    public function raciMatrix(): BelongsTo
    {
        return $this->belongsTo(RaciMatrix::class);
    }

    public function raciAssignment(): BelongsTo
    {
        return $this->belongsTo(RaciAssignment::class);
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
