<?php

namespace App\Services\Governance;

use App\Models\Department;
use App\Models\Risque;
use App\Models\User;
use App\Notifications\RiskEscalatedTransversalNotification;
use Illuminate\Support\Str;

/**
 * Moteur de routage transversal : analyse la description et rattache le risque au département cible (ex. SI).
 */
final class CrossDepartmentRiskRoutingService
{
    /** @var array<string, list<string>> code département => mots-clés (minuscules) */
    private const KEYWORDS_BY_CODE = [
        'IT' => [
            'si', 'informatique', 'cyber', 'sécurité', 'securite', 'sauvegarde', 'backup',
            'serveur', 'mot de passe', 'password', 'réseau', 'reseau', 'journalisation',
            'journal', 'accès', 'acces', 'utilisateur', 'firewall', 'malware', 'données',
        ],
        'RISQUES' => [
            'conformité', 'conformite', 'contrôle interne', 'maîtrise', 'maitrise',
        ],
        'PILOTAGE' => [
            'performance', 'indicateur', 'pilotage', 'gouvernance', 'stratégique',
        ],
    ];

    public function analyzeAndRoute(Risque $risque): void
    {
        $risque->loadMissing(['actif.processus.mission']);

        $sourceId = $risque->source_department_id
            ?? $risque->actif?->processus?->mission?->department_id;

        $targetDepartment = $this->detectTargetDepartment($risque->description ?? '');
        if ($targetDepartment === null) {
            return;
        }

        if ($sourceId !== null && (int) $targetDepartment->id === (int) $sourceId) {
            return;
        }

        $changed = false;
        if ($risque->target_department_id !== $targetDepartment->id) {
            $risque->target_department_id = $targetDepartment->id;
            $changed = true;
        }
        if (! $risque->cross_department && $sourceId !== null && (int) $sourceId !== (int) $targetDepartment->id) {
            $risque->cross_department = true;
            $changed = true;
        }
        if (! $risque->shared) {
            $risque->shared = true;
            $changed = true;
        }

        if ($changed) {
            $risque->saveQuietly();
            $this->notifyDepartmentUsers($risque, $targetDepartment);
        }
    }

    public function detectTargetDepartment(string $description): ?Department
    {
        $haystack = Str::lower($description);

        foreach (self::KEYWORDS_BY_CODE as $code => $keywords) {
            foreach ($keywords as $kw) {
                if (Str::contains($haystack, $kw)) {
                    return Department::query()->where('code', $code)->where('active', true)->first();
                }
            }
        }

        return null;
    }

    private function notifyDepartmentUsers(Risque $risque, Department $department): void
    {
        User::query()
            ->where('department_id', $department->id)
            ->where('active', true)
            ->each(function (User $user) use ($risque, $department): void {
                $user->notify(new RiskEscalatedTransversalNotification($risque, $department));
            });
    }
}
