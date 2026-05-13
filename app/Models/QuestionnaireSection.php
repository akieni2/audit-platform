<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionnaireSection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'questionnaire_template_id',
        'title',
        'description',
        'sort_order',
        'source_section_id',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'source_section_id' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class, 'questionnaire_template_id')->withTrashed();
    }

    public function sourceSection(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_section_id')->withTrashed();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionnaireQuestion::class)->orderBy('sort_order');
    }
}
