<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Constat extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_TEAM_REVIEW = 'team_review';

    public const STATUS_MISSION_VALIDATED = 'mission_validated';

    public const STATUS_CONTRADICTORY = 'contradictory';

    public const STATUS_FINAL = 'final';

    protected $attributes = ['status' => self::STATUS_DRAFT, 'version' => 1];

    protected $fillable = [
        'reference', 'mission_id', 'service_id', 'entretien_response_id',
        'questionnaire_question_id', 'criterion', 'condition_observed',
        'description', 'cause', 'consequence', 'gravite', 'recommandation',
        'status', 'version', 'created_by', 'reviewed_by', 'reviewed_at',
        'validated_by', 'validated_at', 'metadata',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'validated_at' => 'datetime', 'metadata' => 'array'];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_TEAM_REVIEW => 'Revue de l’équipe',
            self::STATUS_MISSION_VALIDATED => 'Validé par le chef de mission',
            self::STATUS_CONTRADICTORY => 'Phase contradictoire',
            self::STATUS_FINAL => 'Définitif',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(EntretienResponse::class, 'entretien_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestion::class, 'questionnaire_question_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by')->withTrashed();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ConstatReview::class)->latest();
    }

    public function auditeeResponses(): HasMany
    {
        return $this->hasMany(ConstatAuditeeResponse::class)->latest('responded_at');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(AuditRecommendation::class);
    }

    public function evidences(): BelongsToMany
    {
        return $this->belongsToMany(MissionDocument::class, 'constat_evidences')->withPivot(['linked_by', 'comment'])->withTimestamps();
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('mission', fn (Builder $q) => $q->visibleToUser($user));
    }
}
