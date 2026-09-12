<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstatReview extends Model
{
    protected $fillable = ['constat_id', 'user_id', 'stage', 'decision', 'comment', 'snapshot'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
