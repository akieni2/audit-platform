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
        'questionnaire_question_id',
        'mission_audit_group_id',
        'uploaded_by',
        'filename',
        'original_name',
        'mime_type',
        'disk',
        'path',
        'checksum_sha256',
        'size',
        'category',
        'expected_document_label',
        'receipt_status',
        'description',
        'version',
        'provided_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size' => 'integer',
            'version' => 'integer',
            'provided_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function entretien(): BelongsTo
    {
        return $this->belongsTo(Entretien::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function questionnaireQuestion(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestion::class)->withTrashed();
    }

    public function auditGroup(): BelongsTo
    {
        return $this->belongsTo(MissionAuditGroup::class, 'mission_audit_group_id');
    }

    /** @return array<string, string> */
    public static function receiptStatusLabels(): array
    {
        return [
            'received' => 'Reçu',
            'partial' => 'Partiel / incomplet',
            'to_review' => 'À examiner',
        ];
    }
}
