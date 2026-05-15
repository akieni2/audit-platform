<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{
    protected $fillable = [
        'ai_conversation_id',
        'tenant_context_id',
        'mission_id',
        'user_id',
        'recommendation_type',
        'confidence_level',
        'title',
        'summary',
        'rationale',
        'payload',
        'requires_human_validation',
        'accepted',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'requires_human_validation' => 'boolean',
            'accepted' => 'boolean',
            'accepted_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
