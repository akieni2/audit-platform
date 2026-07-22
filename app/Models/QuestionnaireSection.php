<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionnaireSection extends Model
{
    use SoftDeletes;

    public const TYPE_THEME = 'theme';

    public const TYPE_THEMATIC = 'thematic';

    public const TYPE_SUBTHEME = 'subtheme';

    protected $fillable = [
        'questionnaire_template_id',
        'title',
        'description',
        'section_type',
        'parent_section_id',
        'sort_order',
        'source_section_id',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'parent_section_id' => 'integer',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_section_id')->withTrashed();
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_section_id')->orderBy('sort_order');
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_THEME => 'Thème',
            self::TYPE_THEMATIC => 'Thématique',
            self::TYPE_SUBTHEME => 'Sous-thématique',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->section_type] ?? 'Thème';
    }
}
