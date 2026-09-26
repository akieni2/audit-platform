<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfdtCourseResource extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['required' => 'boolean'];
    }

    public function course() { return $this->belongsTo(CfdtCourse::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function progress() { return $this->hasMany(CfdtResourceProgress::class, 'resource_id'); }

    public function youtubeEmbedUrl(): ?string
    {
        if ($this->type !== 'video' || blank($this->external_url)) return null;
        if (! preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $this->external_url, $matches)) return null;

        return 'https://www.youtube-nocookie.com/embed/'.$matches[1];
    }
}
