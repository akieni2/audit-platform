<?php

namespace App\Models;

use App\Domain\Workflow\Enums\WorkflowTemplateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'name',
        'slug',
        'description',
        'code',
        'active',
        'is_system',
        'version',
        'status',
        'created_by',
        'updated_by',
        'published_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_system' => 'boolean',
            'version' => 'integer',
            'status' => WorkflowTemplateStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(WorkflowStage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class);
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
