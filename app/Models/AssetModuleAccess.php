<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetModuleAccess extends Model
{
    protected $fillable = ['department_id', 'default_role', 'inherit_to_children', 'active', 'granted_by'];

    protected function casts(): array
    {
        return ['inherit_to_children' => 'boolean', 'active' => 'boolean'];
    }

    public static function roleRank(string $role): int
    {
        return ['viewer' => 1, 'contributor' => 2, 'validator' => 3, 'administrator' => 4][$role] ?? 0;
    }
}
