<?php

namespace App\Models;

use App\Domain\Swot\Enums\SwotPriorityLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwotRecommendation extends Model
{
    protected $fillable = [
        'swot_template_id',
        'swot_analysis_id',
        'swot_entry_id',
        'mission_id',
        'department_id',
        'title',
        'description',
        'priority_level',
        'priority_index',
        'owner_role',
        'status',
        'metadata',
        'due_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'priority_level' => SwotPriorityLevel::class,
            'priority_index' => 'decimal:2',
            'metadata' => 'array',
            'due_at' => 'date',
        ];
    }

    public function swotTemplate(): BelongsTo
    {
        return $this->belongsTo(SwotTemplate::class);
    }

    public function swotAnalysis(): BelongsTo
    {
        return $this->belongsTo(SwotAnalysis::class);
    }

    public function swotEntry(): BelongsTo
    {
        return $this->belongsTo(SwotEntry::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
