<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionnaireQuestion extends Model
{
    use SoftDeletes;

    public const TYPE_BOOLEAN_NA = 'boolean_na';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_SELECT = 'select';

    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPE_RADIO = 'radio';

    public const TYPE_DATE = 'date';

    public const TYPE_NUMBER = 'number';

    public const TYPE_RISK_CAPTURE = 'risk_capture';

    /**
     * @return list<string>
     */
    public static function questionTypes(): array
    {
        return [
            self::TYPE_BOOLEAN_NA,
            self::TYPE_TEXTAREA,
            self::TYPE_SELECT,
            self::TYPE_CHECKBOX,
            self::TYPE_RADIO,
            self::TYPE_DATE,
            self::TYPE_NUMBER,
            self::TYPE_RISK_CAPTURE,
        ];
    }

    protected $fillable = [
        'questionnaire_section_id',
        'code',
        'question',
        'help_text',
        'question_type',
        'required',
        'allows_observation',
        'allows_risk_detection',
        'expected_documents',
        'risk_category',
        'risk_level',
        'sort_order',
        'active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'allows_observation' => 'boolean',
            'allows_risk_detection' => 'boolean',
            'active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireSection::class, 'questionnaire_section_id')->withTrashed();
    }

    public function entretienResponses(): HasMany
    {
        return $this->hasMany(EntretienResponse::class, 'questionnaire_question_id');
    }

    public function identifiedRisks(): HasMany
    {
        return $this->hasMany(IdentifiedRisk::class, 'questionnaire_question_id');
    }
}
