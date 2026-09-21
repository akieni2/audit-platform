<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MissionDocumentRequest extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_TO_REVIEW = 'to_review';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_UNAVAILABLE = 'unavailable';

    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'mission_id', 'service_id', 'questionnaire_question_id', 'mission_audit_group_id',
        'requested_by', 'assigned_to', 'reference', 'label', 'category', 'status',
        'priority', 'is_required', 'requested_at', 'due_at', 'last_reminded_at',
        'reviewed_at', 'closed_at', 'notes', 'review_notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'requested_at' => 'datetime',
            'due_at' => 'date',
            'last_reminded_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'closed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'À préparer',
            self::STATUS_REQUESTED => 'Demandé',
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PARTIAL => 'Reçu partiellement',
            self::STATUS_RECEIVED => 'Reçu',
            self::STATUS_TO_REVIEW => 'À examiner',
            self::STATUS_ACCEPTED => 'Accepté',
            self::STATUS_REJECTED => 'Rejeté / complément demandé',
            self::STATUS_UNAVAILABLE => 'Indisponible',
            self::STATUS_NOT_APPLICABLE => 'Non applicable',
            self::STATUS_CANCELLED => 'Annulé',
        ];
    }

    /** @return list<string> */
    public static function closedStatuses(): array
    {
        return [self::STATUS_ACCEPTED, self::STATUS_UNAVAILABLE, self::STATUS_NOT_APPLICABLE, self::STATUS_CANCELLED];
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && ! in_array($this->status, self::closedStatuses(), true);
    }

    public function statusLabel(): string
    {
        return $this->isOverdue() ? 'En retard' : (self::statusLabels()[$this->status] ?? $this->status);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestion::class, 'questionnaire_question_id')->withTrashed();
    }

    public function auditGroup(): BelongsTo
    {
        return $this->belongsTo(MissionAuditGroup::class, 'mission_audit_group_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MissionDocument::class);
    }
}
