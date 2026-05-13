<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reponse extends Model
{
    protected $fillable = [
        'entretien_id',
        'question_id',
        'reponse',
        'commentaire',
    ];

    protected $appends = [
        'observation',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function entretien(): BelongsTo
    {
        return $this->belongsTo(Entretien::class);
    }

    public function getObservationAttribute(): ?string
    {
        return $this->commentaire;
    }

    public function setObservationAttribute(?string $value): void
    {
        $this->attributes['commentaire'] = $value;
    }
}
