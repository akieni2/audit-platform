<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiExecutionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ai_conversation_id',
        'tenant_context_id',
        'user_id',
        'driver',
        'status',
        'prompt_hash',
        'latency_ms',
        'token_estimate',
        'request_meta',
        'response_meta',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_meta' => 'array',
            'response_meta' => 'array',
            'executed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
