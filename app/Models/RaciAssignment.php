<?php

namespace App\Models;

use App\Domain\Raci\Enums\RaciRoleType;
use App\Domain\Raci\Enums\RaciResponsibilityLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaciAssignment extends Model
{
    protected $fillable = [
        'raci_template_id',
        'raci_matrix_id',
        'raci_role_id',
        'mission_id',
        'department_id',
        'service_id',
        'assigned_user_id',
        'process_label',
        'process_sort_order',
        'role_type',
        'responsibility_level',
        'status',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'role_type' => RaciRoleType::class,
            'responsibility_level' => RaciResponsibilityLevel::class,
            'process_sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function raciTemplate(): BelongsTo
    {
        return $this->belongsTo(RaciTemplate::class);
    }

    public function raciMatrix(): BelongsTo
    {
        return $this->belongsTo(RaciMatrix::class);
    }

    public function raciRole(): BelongsTo
    {
        return $this->belongsTo(RaciRole::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id')->withTrashed();
    }

    public function validations(): HasMany
    {
        return $this->hasMany(RaciValidation::class)->orderByDesc('id');
    }
}
