<?php

namespace App\Models;

use App\Domain\Raci\Enums\RaciValidationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaciValidation extends Model
{
    protected $fillable = [
        'raci_template_id',
        'raci_matrix_id',
        'raci_assignment_id',
        'mission_id',
        'validator_user_id',
        'status',
        'notes',
        'metadata',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RaciValidationStatus::class,
            'metadata' => 'array',
            'validated_at' => 'datetime',
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

    public function raciAssignment(): BelongsTo
    {
        return $this->belongsTo(RaciAssignment::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_user_id')->withTrashed();
    }
}
