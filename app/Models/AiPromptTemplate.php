<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptTemplate extends Model
{
    protected $fillable = [
        'department_id',
        'slug',
        'name',
        'context_type',
        'system_prompt',
        'user_prompt_template',
        'active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
