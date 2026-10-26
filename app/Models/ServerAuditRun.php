<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerAuditRun extends Model
{
    protected $fillable = ['run_uuid', 'mission_id', 'server_audit_asset_id', 'submitted_by', 'raw_document_id', 'collector_version', 'status', 'source_ip', 'payload_sha256', 'started_at', 'completed_at', 'summary'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime', 'summary' => 'array'];
    }

    public function mission(): BelongsTo { return $this->belongsTo(Mission::class); }
    public function asset(): BelongsTo { return $this->belongsTo(ServerAuditAsset::class, 'server_audit_asset_id'); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by')->withTrashed(); }
    public function rawDocument(): BelongsTo { return $this->belongsTo(MissionDocument::class, 'raw_document_id'); }
    public function results(): HasMany { return $this->hasMany(ServerAuditResult::class); }
}
