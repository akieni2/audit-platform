<?php

namespace App\Models;

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

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function entretien(): BelongsTo
    {
        return $this->belongsTo(Entretien::class);
    }

    public function questionnaireQuestion(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestion::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
