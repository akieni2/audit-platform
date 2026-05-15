<?php

namespace App\Models;

use App\Domain\Raci\Enums\RaciRoleType;
use App\Domain\Raci\Enums\RaciResponsibilityLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaciRole extends Model
{
    protected $fillable = [
        'raci_template_id',
        'department_id',
        'name',
        'code',
        'role_type',
        'responsibility_level',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'role_type' => RaciRoleType::class,
            'responsibility_level' => RaciResponsibilityLevel::class,
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function raciTemplate(): BelongsTo
    {
        return $this->belongsTo(RaciTemplate::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RaciAssignment::class)->orderBy('process_sort_order')->orderBy('id');
    }
}
