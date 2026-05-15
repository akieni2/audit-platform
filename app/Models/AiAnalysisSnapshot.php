<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysisSnapshot extends Model
{
    protected $fillable = [
        'ai_conversation_id',
        'mission_id',
        'context_type',
        'analysis_scope',
        'input_snapshot',
        'output_snapshot',
        'confidence_level',
        'driver',
        'integrity_hash',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
            'output_snapshot' => 'array',
            'captured_at' => 'datetime',
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
}
