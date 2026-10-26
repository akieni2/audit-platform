<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerAuditResult extends Model
{
    protected $fillable = ['server_audit_run_id', 'server_audit_control_definition_id', 'control_code', 'status', 'criticality', 'expected', 'observed', 'raw_output', 'identified_risk_id', 'metadata'];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function run(): BelongsTo { return $this->belongsTo(ServerAuditRun::class, 'server_audit_run_id'); }
    public function identifiedRisk(): BelongsTo { return $this->belongsTo(IdentifiedRisk::class); }
    public function controlDefinition(): BelongsTo { return $this->belongsTo(ServerAuditControlDefinition::class, 'server_audit_control_definition_id'); }
}
