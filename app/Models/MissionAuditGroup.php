<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MissionAuditGroup extends Model
{
    protected $fillable = [
        'mission_id', 'name', 'questionnaire_template_id', 'service_id',
        'interviewed_person', 'interviewed_role', 'objective', 'status', 'created_by',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function questionnaireTemplate()
    {
        return $this->belongsTo(QuestionnaireTemplate::class)->withTrashed();
    }

    public function service()
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function members()
    {
        return $this->belongsToMany(MissionTeamMember::class, 'mission_audit_group_members')->withTimestamps();
    }

    public function imports()
    {
        return $this->hasMany(QuestionnaireDocumentImport::class)->latest('id');
    }
}
