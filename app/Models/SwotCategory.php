<?php

namespace App\Models;

use App\Domain\Swot\Enums\SwotCategoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SwotCategory extends Model
{
    protected $fillable = [
        'swot_template_id',
        'name',
        'code',
        'category_type',
        'description',
        'weight',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'category_type' => SwotCategoryType::class,
            'weight' => 'decimal:2',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function swotTemplate(): BelongsTo
    {
        return $this->belongsTo(SwotTemplate::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SwotEntry::class)->orderBy('sort_order')->orderBy('id');
    }
}
