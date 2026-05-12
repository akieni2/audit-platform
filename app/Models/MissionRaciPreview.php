<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Préparation Phase 2C — matrice RACI par mission / service (métadonnées IA-ready). */
class MissionRaciPreview extends Model
{
    protected $table = 'mission_raci_previews';

    protected $fillable = [
        'mission_id',
        'service_id',
        'process_label',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
