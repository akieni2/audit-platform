<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntretienResponse extends Model
{
    protected $fillable = [
        'entretien_id',
        'questionnaire_question_id',
        'answer_boolean',
        'answer_text',
        'answer_json',
        'observation',
        'uploaded_documents_metadata',
        'detected_risk',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'answer_boolean' => 'boolean',
            'answer_json' => 'array',
            'uploaded_documents_metadata' => 'array',
        ];
    }

    public function entretien(): BelongsTo
    {
        return $this->belongsTo(Entretien::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestion::class, 'questionnaire_question_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
