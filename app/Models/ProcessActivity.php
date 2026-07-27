<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessActivity extends Model
{
    protected $fillable = ['institutional_process_id', 'sequence', 'title', 'description', 'estimated_duration_minutes', 'responsible_user_id', 'produced_documents'];

    public function process(): BelongsTo
    {
        return $this->belongsTo(InstitutionalProcess::class, 'institutional_process_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
