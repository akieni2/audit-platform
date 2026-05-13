<?php

namespace App\Models;

use App\Domain\Risk\Enums\CriticalityLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentifiedRisk extends Model
{
    protected $fillable = [
        'mission_id',
        'service_id',
        'entretien_id',
        'questionnaire_question_id',
        'title',
        'description',
        'category',
        'probability',
        'impact',
        'criticality',
        'recommendation',
        'ai_generated',
        'validated_by_human',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ai_generated' => 'boolean',
            'validated_by_human' => 'boolean',
        ];
    }

    public static function normalizeCriticality(?string $value): ?string
    {
        return CriticalityLevel::fromMixed($value)?->value;
    }

    public function criticalityLabel(): ?string
    {
        return CriticalityLevel::fromMixed($this->criticality)?->label();
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function entretien(): BelongsTo
    {
        return $this->belongsTo(Entretien::class);
    }

    public function questionnaireQuestion(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestion::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
