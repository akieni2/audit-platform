<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaciSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'raci_template_id',
        'raci_matrix_id',
        'mission_id',
        'department_id',
        'workflow_instance_id',
        'snapshot_hash',
        'snapshot_payload',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_payload' => 'array',
            'captured_at' => 'datetime',
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
}
