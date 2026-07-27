<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessElement extends Model
{
    protected $fillable = ['institutional_process_id', 'type', 'name', 'description'];

    public function process(): BelongsTo
    {
        return $this->belongsTo(InstitutionalProcess::class, 'institutional_process_id');
    }
}
