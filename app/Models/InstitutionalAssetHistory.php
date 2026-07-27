<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionalAssetHistory extends Model
{
    public $timestamps = false;

    protected $table = 'institutional_asset_history';

    protected $fillable = ['institutional_asset_id', 'event_type', 'changes', 'comment', 'actor_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'occurred_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
