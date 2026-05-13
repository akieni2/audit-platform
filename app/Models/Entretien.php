<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Entretien extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'mission_id',
        'service_id',
        'questionnaire_template_id',
        'questionnaire_snapshot',
        'questionnaire_snapshot_version',
        'questionnaire_snapshot_hash',
        'questionnaire_snapshot_taken_at',
        'conducted_by',
        'interviewed_person',
        'interviewed_role',
        'conducted_at',
        'status',
        'validation_status',
        'synthesis',
        'responsable_nom',
        'role',
        'chef_hierarchique',
        'auditeur',
        'date_entretien',
        'email',
        'telephone',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'questionnaire_snapshot' => 'array',
            'conducted_at' => 'datetime',
            'questionnaire_snapshot_taken_at' => 'datetime',
            'date_entretien' => 'date',
        ];
    }

    /*
    |-----------------------------------
    | RELATIONS
    |-----------------------------------
    */

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function questionnaireTemplate()
    {
        return $this->belongsTo(QuestionnaireTemplate::class, 'questionnaire_template_id')->withTrashed();
    }

    public function conductor()
    {
        return $this->belongsTo(User::class, 'conducted_by')->withTrashed();
    }

    /** Réponses au questionnaire dynamique (Phase 1.5). */
    public function questionnaireResponses()
    {
        return $this->hasMany(EntretienResponse::class);
    }

    public function identifiedRisks()
    {
        return $this->hasMany(IdentifiedRisk::class);
    }

    public function missionDocuments()
    {
        return $this->hasMany(MissionDocument::class, 'entretien_id');
    }

    /**
     * @param  Builder<Entretien>  $query
     * @return Builder<Entretien>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->whereHas('mission', fn (Builder $mq) => $mq->visibleToUser($user))
                ->orWhereHas(
                    'service.mission',
                    fn (Builder $mq) => $mq->visibleToUser($user)
                );
        });
    }

    /** Réponses legacy conservées pour compatibilité transitoire. */
    public function reponses()
    {
        return $this->hasMany(Reponse::class);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_COMPLETED => 'Complété',
            self::STATUS_VALIDATED => 'Validé',
            self::STATUS_ARCHIVED => 'Archivé',
        ];
    }

    /** Progression questionnaire dynamique (0–100), ou null si sans modèle. */
    public function questionnaireCompletionPercent(): ?int
    {
        if ($this->questionnaire_template_id === null && empty($this->questionnaire_snapshot)) {
            return null;
        }

        $total = 0;

        if (is_array($this->questionnaire_snapshot) && isset($this->questionnaire_snapshot['template']['sections'])) {
            $total = collect($this->questionnaire_snapshot['template']['sections'])
                ->sum(fn ($section) => count($section['questions'] ?? []));
        }

        if ($total === 0) {
            $this->loadMissing([
                'questionnaireTemplate.sections.questions' => fn ($q) => $q->where('active', true),
            ]);

            $template = $this->questionnaireTemplate;
            if ($template === null) {
                return null;
            }

            $total = (int) $template->sections->sum(fn ($section) => $section->questions->count());
        }
        if ($total === 0) {
            return null;
        }

        $answered = match (true) {
            array_key_exists('questionnaire_responses_count', $this->getAttributes()) => (int) $this->getAttribute('questionnaire_responses_count'),
            $this->relationLoaded('questionnaireResponses') => $this->questionnaireResponses->count(),
            default => (int) $this->questionnaireResponses()->count(),
        };

        return (int) min(100, max(0, (int) round(100 * $answered / $total)));
    }
}
