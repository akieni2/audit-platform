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
        'submitted_for_review_at',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
        'approved_by',
        'approval_notes',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_notes',
        'promoted_at',
        'promotion_notes',
        'owner_user_id',
        'owner_department_id',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ai_generated' => 'boolean',
            'validated_by_human' => 'boolean',
            'submitted_for_review_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'promoted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function lifecycleLabels(): array
    {
        return RiskLifecycleStatus::labels();
    }

    public static function normalizeCriticality(?string $value): ?string
    {
        return CriticalityLevel::fromMixed($value)?->value;
    }

    public function criticalityLabel(): ?string
    {
        return CriticalityLevel::fromMixed($this->criticality)?->label();
    }

    public function lifecycleLabel(): string
    {
        return RiskLifecycleStatus::fromMixed($this->lifecycle_status)->label();
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

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by')->withTrashed();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id')->withTrashed();
    }

    public function ownerDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'owner_department_id');
    }

    public function promotedRisk(): HasOne
    {
        return $this->hasOne(Risque::class, 'identified_risk_id');
    }
}
