<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionalAssetControl extends Model
{
    protected $fillable = ['institutional_asset_id', 'name', 'description', 'status', 'responsible_user_id', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'date'];
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
