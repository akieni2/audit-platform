<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionCorrectiveUpdate extends Model
{
    protected $fillable = ['action_corrective_id', 'user_id', 'progress_percent', 'status', 'comment'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
