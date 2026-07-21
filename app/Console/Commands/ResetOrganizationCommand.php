<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\User;
use App\Services\Governance\OrganizationDeletionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResetOrganizationCommand extends Command
{
    protected $signature = 'organization:reset
        {--confirm= : Phrase obligatoire PURGER-ORGANISATION-DGCPT}
        {--dry-run : Afficher les volumes sans supprimer}
        {--force : Autoriser l’exécution en production}';

    protected $description = 'Supprime toutes les structures et tous les utilisateurs sauf les super administrateurs';

    public function handle(OrganizationDeletionService $deletion): int
    {
        $protectedIds = User::withTrashed()
            ->where(function ($query): void {
                $query->whereRaw('LOWER(email) = ?', [strtolower((string) config('dgcpt.super_admin_email'))])
                    ->orWhereHas('institutionalRole', fn ($role) => $role->where('slug', 'super_admin'));
            })
            ->pluck('id');

        if ($protectedIds->isEmpty()) {
            throw new RuntimeException('Aucun compte super administrateur protégé n’a été trouvé. Purge annulée.');
        }

        $userCount = User::withTrashed()->whereNotIn('id', $protectedIds)->count();
        $departmentCount = Department::query()->count();

        $this->table(['Élément', 'Nombre'], [
            ['Super administrateurs conservés', $protectedIds->count()],
            ['Utilisateurs à supprimer', $userCount],
            ['Structures à supprimer', $departmentCount],
        ]);

        if ($this->option('dry-run')) {
            $this->info('Aperçu terminé : aucune donnée supprimée.');

            return self::SUCCESS;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('L’option --force est obligatoire en production.');

            return self::FAILURE;
        }

        if ($this->option('confirm') !== 'PURGER-ORGANISATION-DGCPT') {
            $this->error('Phrase de confirmation incorrecte. Utilisez --confirm=PURGER-ORGANISATION-DGCPT.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($protectedIds, $deletion): void {
            DB::table('sessions')->whereNotNull('user_id')->whereNotIn('user_id', $protectedIds)->delete();
            DB::table('password_reset_tokens')->delete();

            User::withTrashed()->whereNotIn('id', $protectedIds)->get()->each->forceDelete();

            Department::query()->whereNull('parent_department_id')->get()->each(
                fn (Department $department) => $deletion->deleteTree($department)
            );
            Department::query()->get()->each(
                fn (Department $department) => $deletion->deleteTree($department)
            );

            User::withTrashed()->whereIn('id', $protectedIds)->update([
                'department_id' => null,
                'registration_requested_department_id' => null,
            ]);
        });

        $this->info('Réinitialisation terminée. Les super administrateurs ont été conservés.');

        return self::SUCCESS;
    }
}
