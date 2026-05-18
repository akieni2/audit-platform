<?php

namespace App\Models\Dgcpt;

use App\Domain\Dgcpt\Enums\TreasuryEntityType;
use App\Models\Department;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreasuryEntity extends Model
{
    protected $fillable = [
        'name',
        'code',
        'entity_type',
        'province',
        'country',
        'parent_entity_id',
        'department_id',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_entity_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_entity_id')->orderBy('name');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function treasuryServices(): HasMany
    {
        return $this->hasMany(TreasuryService::class);
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    public function entityTypeEnum(): ?TreasuryEntityType
    {
        return TreasuryEntityType::fromMixed($this->entity_type);
    }

    public function entityTypeLabel(): string
    {
        return $this->entityTypeEnum()?->label() ?? (string) $this->entity_type;
    }

    /**
     * @param  Builder<TreasuryEntity>  $query
     * @return Builder<TreasuryEntity>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @param  Builder<TreasuryEntity>  $query
     * @return Builder<TreasuryEntity>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->approval_status !== User::APPROVAL_STATUS_APPROVED || ! $user->active) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->canViewAllInstitutionalData()) {
            return $query;
        }

        $deptId = $user->department_id;
        if ($deptId === null) {
            return $query;
        }

        return $query->where(function (Builder $outer) use ($deptId) {
            $outer->whereNull('department_id')
                ->orWhere('department_id', $deptId);
        });
    }
}
