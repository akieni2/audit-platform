<?php

namespace App\Models;

use App\Domain\Swot\Enums\SwotImpactLevel;
use App\Domain\Swot\Enums\SwotPriorityLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwotEntry extends Model
{
    protected $fillable = [
        'swot_template_id',
        'swot_category_id',
        'department_id',
        'title',
        'description',
        'impact_level',
        'priority_level',
        'weight',
        'source_type',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'impact_level' => SwotImpactLevel::class,
            'priority_level' => SwotPriorityLevel::class,
            'weight' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function swotTemplate(): BelongsTo
    {
        return $this->belongsTo(SwotTemplate::class);
    }

    public function swotCategory(): BelongsTo
    {
        return $this->belongsTo(SwotCategory::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
