<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'active',
        'supervisor_user_id',
        'accent_color',
        'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id')->withTrashed();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }
}
