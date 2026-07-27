<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionalAsset extends Model
{
    protected $fillable = ['category_id', 'owner_department_id', 'owner_user_id', 'asset_tag', 'name', 'description', 'location', 'commissioned_at', 'manufacturer', 'model', 'serial_number', 'condition', 'status', 'estimated_value', 'availability_score', 'confidentiality_score', 'integrity_score', 'traceability_score', 'probability_score', 'impact_score', 'criticality_score', 'criticality', 'interrupted_services', 'impacted_users', 'impacted_applications', 'fallback_solution', 'rto_minutes', 'rpo_minutes', 'has_backup', 'has_redundancy', 'single_point_of_failure', 'obsolete', 'visibility', 'specific_attributes', 'created_by'];

    protected function casts(): array
    {
        return ['commissioned_at' => 'date', 'estimated_value' => 'decimal:2', 'has_backup' => 'boolean', 'has_redundancy' => 'boolean', 'single_point_of_failure' => 'boolean', 'obsolete' => 'boolean', 'specific_attributes' => 'array'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InstitutionalAssetCategory::class, 'category_id');
    }

    public function ownerDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'owner_department_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function participatingDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'institutional_asset_department');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'institutional_asset_dependencies', 'asset_id', 'depends_on_asset_id')->withPivot(['dependency_type', 'description', 'critical']);
    }

    public function dependentAssets(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'institutional_asset_dependencies', 'depends_on_asset_id', 'asset_id');
    }

    public function processes(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionalProcess::class, 'institutional_asset_process');
    }

    public function controls(): HasMany
    {
        return $this->hasMany(InstitutionalAssetControl::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InstitutionalAssetDocument::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(InstitutionalAssetHistory::class)->orderByDesc('occurred_at');
    }

    public function scopeVisibleTo(Builder $q, User $u): Builder
    {
        if ($u->isInstitutionalSuperAdmin()) {
            return $q;
        }$ids = Department::ancestryIds((int) $u->department_id);

        return $q->where(fn (Builder $b) => $b->where('visibility', 'institutional')->where('status', 'active')->orWhereIn('owner_department_id', $ids)->orWhereHas('participatingDepartments', fn (Builder $p) => $p->whereIn('departments.id', $ids)));
    }

    public static function criticalityLabels(): array
    {
        return ['low' => 'Faible', 'medium' => 'Modérée', 'high' => 'Importante', 'critical' => 'Critique'];
    }

    public static function statusLabels(): array
    {
        return ['draft' => 'Brouillon', 'active' => 'En service', 'maintenance' => 'Maintenance', 'unavailable' => 'Indisponible', 'retired' => 'Retiré', 'archived' => 'Archivé'];
    }
}
