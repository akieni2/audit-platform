<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessHistory extends Model
{
    public $timestamps = false;

    protected $table = 'process_history';

    protected $fillable = ['institutional_process_id', 'event_type', 'version', 'changes', 'comment', 'actor_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'occurred_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
