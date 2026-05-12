<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MissionDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mission_id',
        'service_id',
        'entretien_id',
        'uploaded_by',
        'filename',
        'original_name',
        'mime_type',
        'disk',
        'path',
        'size',
        'category',
        'description',
        'version',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size' => 'integer',
            'version' => 'integer',
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

    public function entretien(): BelongsTo
    {
        return $this->belongsTo(Entretien::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
