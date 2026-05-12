<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionnaireTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'mission_type',
        'department_scope',
        'active',
        'version',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'department_scope' => 'array',
            'active' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(QuestionnaireSection::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function entretiens(): HasMany
    {
        return $this->hasMany(Entretien::class, 'questionnaire_template_id');
    }

    /**
     * Périmètre institutionnel : null ou vide = référentiel national (tous pôles).
     *
     * @param  list<int>|null  $departmentIds
     */
    public function isVisibleToDepartment(?int $departmentId, ?array $departmentIds = null): bool
    {
        $scope = $this->department_scope;
        if ($scope === null || $scope === []) {
            return true;
        }
        $ids = array_map('intval', $scope);
        if ($departmentId !== null && in_array((int) $departmentId, $ids, true)) {
            return true;
        }
        if ($departmentIds !== null) {
            foreach ($departmentIds as $id) {
                if (in_array((int) $id, $ids, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
