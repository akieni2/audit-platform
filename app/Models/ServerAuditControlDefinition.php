<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerAuditControlDefinition extends Model
{
    protected $fillable = [
        'methodology_requirement_id', 'control_measure_id', 'code', 'version', 'title', 'category',
        'criticality', 'control_objective', 'default_expected', 'risk_description', 'treatment_guidance',
        'applicability', 'active', 'metadata',
    ];

    protected function casts(): array
    {
        return ['version' => 'integer', 'applicability' => 'array', 'active' => 'boolean', 'metadata' => 'array'];
    }

    public function references(): HasMany
    {
        return $this->hasMany(ServerAuditControlReference::class)->orderByDesc('is_primary')->orderBy('id');
    }

    public function methodologyRequirement(): BelongsTo
    {
        return $this->belongsTo(MethodologyRequirement::class);
    }

    public function controlMeasure(): BelongsTo
    {
        return $this->belongsTo(ControlMeasure::class);
    }
}
