<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessDocument extends Model
{
    protected $fillable = ['institutional_process_id', 'title', 'document_type', 'path', 'original_name', 'uploaded_by'];

    public function process(): BelongsTo
    {
        return $this->belongsTo(InstitutionalProcess::class, 'institutional_process_id');
    }
}
