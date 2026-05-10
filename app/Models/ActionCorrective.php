<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActionCorrective extends Model
{

    protected $table = 'actions_correctives';

    protected $fillable = [

        'risque_id',
        'description',
        'responsable',
        'date_echeance',
        'statut',
        'recommendation_library_id'

    ];

    public function risque()
    {
        return $this->belongsTo(Risque::class);
    }

    /**
     * @param  Builder<ActionCorrective>  $query
     * @return Builder<ActionCorrective>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('risque', fn (Builder $rq) => $rq->visibleToUser($user));
    }

   public function recommendation()
    {
    return $this->belongsTo(RecommendationLibrary::class,'recommendation_library_id');
    }
    public function isOverdue()
    {
    if($this->statut == 'ferme'){
        return false;
    }

    if(!$this->date_echeance){
        return false;
    }

    return now()->gt($this->date_echeance);
   }

}
