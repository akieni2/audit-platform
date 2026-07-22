<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionnaireDocumentImport extends Model
{
    protected $fillable = [
        'mission_audit_group_id', 'original_name', 'stored_path', 'sha256',
        'status', 'extracted_data', 'analysis_suggestions', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['extracted_data' => 'array', 'analysis_suggestions' => 'array'];
    }

    public function group()
    {
        return $this->belongsTo(MissionAuditGroup::class, 'mission_audit_group_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
