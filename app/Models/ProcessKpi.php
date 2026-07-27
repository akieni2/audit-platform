<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessKpi extends Model
{
    protected $fillable = ['institutional_process_id', 'name', 'unit', 'target_value', 'current_value', 'calculation_method'];

    public function process(): BelongsTo
    {
        return $this->belongsTo(InstitutionalProcess::class, 'institutional_process_id');
    }
}
