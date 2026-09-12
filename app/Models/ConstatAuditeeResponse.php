<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstatAuditeeResponse extends Model
{
    protected $fillable = ['constat_id', 'responded_by', 'department_id', 'position', 'observation', 'proposed_action', 'proposed_owner_user_id', 'proposed_due_date', 'responded_at'];

    protected function casts(): array
    {
        return ['proposed_due_date' => 'date', 'responded_at' => 'datetime'];
    }

    public function respondent()
    {
        return $this->belongsTo(User::class, 'responded_by')->withTrashed();
    }
}
