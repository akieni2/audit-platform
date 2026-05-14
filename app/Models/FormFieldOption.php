<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormFieldOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'form_field_id',
        'label',
        'value',
        'sort_order',
        'is_default',
        'source_option_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'source_option_id' => 'integer',
            'metadata' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class)->withTrashed();
    }

    public function sourceOption(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_option_id')->withTrashed();
    }
}
