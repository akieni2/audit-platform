<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActionCorrective extends Model
{
    protected $table = 'actions_correctives';

    protected $fillable = [

        'risque_id',
        'audit_recommendation_id',
        'description',
        'responsable',
        'owner_user_id',
        'owner_department_id',
        'date_echeance',
        'statut',
        'progress_percent',
        'started_at',
        'completed_at',
        'closure_requested_at',
        'closure_validated_by',
        'closure_validated_at',
        'closure_comment',
        'metadata',
        'recommendation_library_id',

    ];

    protected function casts(): array
    {
        return [
            'date_echeance' => 'date', 'progress_percent' => 'integer',
            'started_at' => 'datetime', 'completed_at' => 'datetime',
            'closure_requested_at' => 'datetime', 'closure_validated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function risque()
    {
        return $this->belongsTo(Risque::class);
    }

    public function auditRecommendation()
    {
        return $this->belongsTo(AuditRecommendation::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id')->withTrashed();
    }

    public function ownerDepartment()
    {
        return $this->belongsTo(Department::class, 'owner_department_id');
    }

    public function updates()
    {
        return $this->hasMany(ActionCorrectiveUpdate::class)->latest();
    }

    public function evidences()
    {
        return $this->belongsToMany(MissionDocument::class, 'action_corrective_evidences')->withPivot(['linked_by', 'comment'])->withTimestamps();
    }

    /**
     * @param  Builder<ActionCorrective>  $query
     * @return Builder<ActionCorrective>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $visible) use ($user): void {
            $visible->whereHas('risque', fn (Builder $rq) => $rq->visibleToUser($user))
                ->orWhereHas('auditRecommendation.mission', fn (Builder $mission) => $mission->visibleToUser($user));
        });
    }

    public function recommendation()
    {
        return $this->belongsTo(RecommendationLibrary::class, 'recommendation_library_id');
    }

    public function isOverdue()
    {
        if ($this->statut == 'ferme') {
            return false;
        }

        if (! $this->date_echeance) {
            return false;
        }

        return now()->gt($this->date_echeance);
    }
}
