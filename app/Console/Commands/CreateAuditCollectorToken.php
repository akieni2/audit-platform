<?php

namespace App\Console\Commands;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateAuditCollectorToken extends Command
{
    protected $signature = 'audit:collector-token {user : ID ou adresse e-mail} {mission : ID de la mission} {--name=server-audit-collector}';
    protected $description = 'Crée un jeton Sanctum limité à la transmission de preuves d’audit serveur.';

    public function handle(): int
    {
        $identity = (string) $this->argument('user');
        $user = User::query()->where('email', $identity)->orWhere('id', ctype_digit($identity) ? (int) $identity : 0)->first();
        $mission = Mission::query()->find($this->argument('mission'));

        if (! $user || ! $mission) {
            $this->error('Utilisateur ou mission introuvable.');
            return self::FAILURE;
        }
        if (! Mission::query()->whereKey($mission->id)->visibleToUser($user)->exists()
            || (! $user->canGovernMissionInstitutionally($mission) && ! $user->isMissionOperationalContributor($mission))) {
            $this->error('Cet utilisateur doit être gouverneur ou membre opérationnel de la mission.');
            return self::FAILURE;
        }

        $token = $user->createToken((string) $this->option('name'), ['audit-collector:submit']);
        $this->warn('Copiez maintenant ce jeton : il ne sera plus affiché.');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->info('MISSION_ID='.$mission->id);
        $this->info('ASSET_UUID='.Str::uuid());
        return self::SUCCESS;
    }
}
