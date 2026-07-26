<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrespondenceMovement extends Model
{
    protected $fillable = [
        'correspondence_record_id',
        'event_type',
        'from_department_id',
        'to_department_id',
        'from_user_id',
        'to_user_id',
        'notes',
        'occurred_at',
        'actor_id',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function correspondenceRecord(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceRecord::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
