<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionWorkflowEvent extends Model
{
    protected $fillable = [
        'mission_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'comment',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
