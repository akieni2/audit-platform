<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditPlan extends Model
{
    protected $fillable = [
        'mission_id',
        'titre',
        'description',
        'niveau',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function programmes(): HasMany
    {
        return $this->hasMany(AuditProgramme::class);
    }
}
