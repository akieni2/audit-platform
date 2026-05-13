<?php

namespace App\Models;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Domain\Risk\Enums\RiskLifecycleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IdentifiedRisk extends Model
{
    protected $fillable = [
        'mission_id',
        'service_id',
        'entretien_id',
        'questionnaire_question_id',
        'source_signature',
        'title',
        'description',
        'category',
        'probability',
        'impact',
        'criticality',
        'lifecycle_status',
        'recommendation',
        'ai_generated',
        'validated_by_human',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'promoted_at',
        'promotion_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ai_generated' => 'boolean',
            'validated_by_human' => 'boolean',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'promoted_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function lifecycleLabels(): array
    {
        return collect(RiskLifecycleStatus::cases())
            ->mapWithKeys(fn (RiskLifecycleStatus $status) => [$status->value => $status->label()])
            ->all();
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function promotedRisk(): HasOne
    {
        return $this->hasOne(Risque::class, 'identified_risk_id');
    }
}
