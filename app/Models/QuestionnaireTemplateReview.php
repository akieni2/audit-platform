<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireTemplateReview extends Model
{
    public const DECISION_APPROVED = 'approved';

    public const DECISION_CHANGES_REQUESTED = 'changes_requested';

    protected $fillable = ['questionnaire_template_id', 'reviewer_id', 'decision', 'comment'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class, 'questionnaire_template_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
