<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdministrativeTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'correspondence_record_id',
        'title',
        'description',
        'priority',
        'status',
        'department_id',
        'owner_id',
        'assignee_id',
        'due_at',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function correspondenceRecord(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceRecord::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->due_at?->isPast()
            && ! in_array($this->status, ['validated', 'closed'], true);
    }

    public static function statusLabels(): array
    {
        return [
            'draft' => 'Brouillon',
            'assigned' => 'Affectée',
            'in_progress' => 'En cours',
            'submitted' => 'Soumise pour validation',
            'validated' => 'Validée',
            'closed' => 'Clôturée',
        ];
    }

    public static function priorityLabels(): array
    {
        return [
            'low' => 'Faible',
            'normal' => 'Normale',
            'high' => 'Élevée',
            'critical' => 'Critique',
        ];
    }
}
