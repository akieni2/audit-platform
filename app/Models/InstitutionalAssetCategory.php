<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionalAssetCategory extends Model
{
    protected $fillable = ['owner_department_id', 'code', 'name', 'description', 'active', 'created_by'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function ownerDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'owner_department_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(InstitutionalAsset::class, 'category_id');
    }
}
