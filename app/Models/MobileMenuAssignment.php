<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileMenuAssignment extends Model
{
    public const SUBJECT_USER = 'user';

    public const SUBJECT_ROLE = 'role';

    public const SUBJECT_DEPARTMENT = 'department';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'menu_key',
        'granted_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by')->withTrashed();
    }

    public function scopeActive($query)
    {
        return $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
