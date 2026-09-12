<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditRecommendation extends Model
{
    protected $fillable = ['reference', 'mission_id', 'constat_id', 'identified_risk_id', 'description', 'priority', 'status', 'owner_user_id', 'owner_department_id', 'due_date', 'created_by', 'validated_by', 'validated_at', 'metadata'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'validated_at' => 'datetime', 'metadata' => 'array'];
    }

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function constat()
    {
        return $this->belongsTo(Constat::class);
    }

    public function identifiedRisk()
    {
        return $this->belongsTo(IdentifiedRisk::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id')->withTrashed();
    }

    public function ownerDepartment()
    {
        return $this->belongsTo(Department::class, 'owner_department_id');
    }

    public function actions()
    {
        return $this->hasMany(ActionCorrective::class);
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('mission', fn (Builder $q) => $q->visibleToUser($user));
    }
}
