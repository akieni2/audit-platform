<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionalProcess extends Model
{
    protected $fillable = ['domain_id', 'owner_department_id', 'owner_user_id', 'code', 'name', 'objective', 'description', 'criticality', 'priority', 'status', 'visibility', 'version', 'maturity_level', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'version' => 'integer', 'maturity_level' => 'integer'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(ProcessDomain::class, 'domain_id');
    }

    public function ownerDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'owner_department_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participatingDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'institutional_process_department')->withPivot('participation_role');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProcessActivity::class)->orderBy('sequence');
    }

    public function elements(): HasMany
    {
        return $this->hasMany(ProcessElement::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProcessDocument::class);
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(ProcessKpi::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ProcessHistory::class)->orderByDesc('occurred_at');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isInstitutionalSuperAdmin()) {
            return $query;
        }
        $departmentIds = Department::ancestryIds((int) $user->department_id);

        return $query->where(function (Builder $q) use ($departmentIds): void {
            $q->where('visibility', 'institutional')->where('status', 'published')
                ->orWhereIn('owner_department_id', $departmentIds)
                ->orWhereHas('participatingDepartments', fn (Builder $p) => $p->whereIn('departments.id', $departmentIds));
        });
    }

    public static function statusLabels(): array
    {
        return ['draft' => 'Brouillon', 'pending_validation' => 'À valider', 'published' => 'Publié', 'revision' => 'En révision', 'archived' => 'Archivé'];
    }

    public static function criticalityLabels(): array
    {
        return ['low' => 'Faible', 'medium' => 'Moyenne', 'high' => 'Élevée', 'critical' => 'Critique'];
    }
}
