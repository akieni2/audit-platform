<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    protected $fillable = [
        'tenant_context_id',
        'user_id',
        'mission_id',
        'context_type',
        'title',
        'status',
        'context_payload',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'context_payload' => 'array',
            'metadata' => 'array',
        ];
    }

    public function tenantContext(): BelongsTo
    {
        return $this->belongsTo(TenantContext::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(AiRecommendation::class)->orderByDesc('id');
    }

    public function executionLogs(): HasMany
    {
        return $this->hasMany(AiExecutionLog::class)->orderByDesc('executed_at');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(AiAnalysisSnapshot::class)->orderByDesc('captured_at');
    }
}
