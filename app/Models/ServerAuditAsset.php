<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerAuditAsset extends Model
{
    protected $fillable = ['asset_uuid', 'mission_id', 'hostname', 'ip_address', 'platform', 'asset_type', 'product', 'product_version', 'operating_system', 'asset_role', 'metadata', 'last_seen_at', 'active'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'last_seen_at' => 'datetime', 'active' => 'boolean'];
    }

    public function mission(): BelongsTo { return $this->belongsTo(Mission::class); }
    public function runs(): HasMany { return $this->hasMany(ServerAuditRun::class); }
}
