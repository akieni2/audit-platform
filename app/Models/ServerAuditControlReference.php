<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerAuditControlReference extends Model
{
    protected $fillable = [
        'server_audit_control_definition_id', 'authority', 'document_title', 'document_version',
        'chapter', 'section', 'provision_reference', 'requirement_summary', 'source_url', 'is_primary', 'metadata',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'metadata' => 'array'];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ServerAuditControlDefinition::class, 'server_audit_control_definition_id');
    }
}
