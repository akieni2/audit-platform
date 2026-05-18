<?php

namespace App\Models\Dgcpt;

use App\Models\Mission;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Service métier DGCPT au sein d'une entité (Informatique, Comptabilité, …).
 * Ne pas confondre avec App\Models\Service (unité auditée rattachée à une mission).
 */
class TreasuryService extends Model
{
    protected $table = 'treasury_services';

    protected $fillable = [
        'treasury_entity_id',
        'name',
        'code',
        'service_type',
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

    public function treasuryEntity(): BelongsTo
    {
        return $this->belongsTo(TreasuryEntity::class);
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    public function missionServices(): HasMany
    {
        return $this->hasMany(Service::class, 'treasury_service_id');
    }

    /**
     * @param  Builder<TreasuryService>  $query
     * @return Builder<TreasuryService>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @param  Builder<TreasuryService>  $query
     * @return Builder<TreasuryService>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('treasuryEntity', fn (Builder $q) => $q->visibleToUser($user));
    }
}
