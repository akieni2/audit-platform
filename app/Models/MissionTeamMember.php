<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionTeamMember extends Model
{
    public const ROLE_CHEF_MISSION = 'chef_mission';

    public const ROLE_INSPECTEUR_VERIFICATEUR = 'inspecteur_verificateur';

    public const ROLE_INSPECTEUR_VERIFICATEUR_ADJOINT = 'inspecteur_verificateur_adjoint';

    public const ROLE_OBSERVATEUR = 'observateur';

    public const ROLE_AGENT = 'agent';

    public const ROLE_EXPERT = 'expert';

    public const ROLE_ASSISTANT = 'assistant';

    /**
     * @return list<string>
     */
    public static function missionRoles(): array
    {
        return [
            self::ROLE_CHEF_MISSION,
            self::ROLE_INSPECTEUR_VERIFICATEUR,
            self::ROLE_INSPECTEUR_VERIFICATEUR_ADJOINT,
            self::ROLE_AGENT,
            self::ROLE_OBSERVATEUR,
            self::ROLE_EXPERT,
            self::ROLE_ASSISTANT,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function missionRoleLabels(): array
    {
        return [
            self::ROLE_CHEF_MISSION => 'Chef de mission',
            self::ROLE_INSPECTEUR_VERIFICATEUR => 'Inspecteur Vérificateur',
            self::ROLE_INSPECTEUR_VERIFICATEUR_ADJOINT => 'Inspecteur Vérificateur Adjoint',
            self::ROLE_AGENT => 'Agent',
            self::ROLE_OBSERVATEUR => 'Observateur',
            self::ROLE_EXPERT => 'Expert',
            self::ROLE_ASSISTANT => 'Assistant',
        ];
    }

    protected $fillable = [
        'mission_id',
        'user_id',
        'mission_role',
        'designation',
        'is_lead',
        'assigned_at',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'is_lead' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Un seul chef de mission métier : les anciens chefs passent en IV jusqu’à réaffectation manuelle.
     */
    public static function demoteOtherChefs(Mission $mission): void
    {
        self::query()
            ->where('mission_id', $mission->id)
            ->where('mission_role', self::ROLE_CHEF_MISSION)
            ->update([
                'mission_role' => self::ROLE_INSPECTEUR_VERIFICATEUR,
                'is_lead' => false,
            ]);
    }
}
