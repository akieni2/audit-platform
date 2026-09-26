<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfdtResourceProgress extends Model
{
    protected $table = 'cfdt_resource_progress';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function enrollment() { return $this->belongsTo(CfdtEnrollment::class); }
    public function resource() { return $this->belongsTo(CfdtCourseResource::class, 'resource_id'); }
}
