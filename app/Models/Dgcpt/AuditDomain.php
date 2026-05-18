<?php

namespace App\Models\Dgcpt;

use App\Models\Mission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditDomain extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function auditTemplates(): HasMany
    {
        return $this->hasMany(AuditTemplate::class);
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    /**
     * @param  Builder<AuditDomain>  $query
     * @return Builder<AuditDomain>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
