<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorrespondenceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'direction',
        'sender',
        'recipient',
        'subject',
        'description',
        'document_type',
        'confidentiality',
        'urgency',
        'status',
        'received_at',
        'deadline_at',
        'current_department_id',
        'current_assignee_id',
        'document_path',
        'qr_token',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'deadline_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'current_department_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CorrespondenceMovement::class)->orderByDesc('occurred_at');
    }

    public function administrativeTasks(): HasMany
    {
        return $this->hasMany(AdministrativeTask::class);
    }

    public function isOverdue(): bool
    {
        return $this->deadline_at?->isPast()
            && ! in_array($this->status, ['closed', 'archived'], true);
    }

    public static function urgencyLabels(): array
    {
        return [
            'low' => 'Faible',
            'standard' => 'Standard',
            'normal' => 'Normal',
            'urgent' => 'Urgent',
            'very_urgent' => 'Très urgent',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'registered' => 'Enregistré',
            'assigned' => 'Affecté',
            'in_progress' => 'En traitement',
            'answered' => 'Réponse préparée',
            'closed' => 'Clôturé',
            'archived' => 'Archivé',
        ];
    }
}
