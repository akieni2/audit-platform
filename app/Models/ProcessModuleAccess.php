<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessModuleAccess extends Model
{
    protected $fillable = ['department_id', 'default_role', 'inherit_to_children', 'active', 'granted_by'];

    protected function casts(): array
    {
        return ['inherit_to_children' => 'boolean', 'active' => 'boolean'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public static function roleRank(string $role): int
    {
        return ['viewer' => 1, 'contributor' => 2, 'validator' => 3, 'administrator' => 4][$role] ?? 0;
    }
}
