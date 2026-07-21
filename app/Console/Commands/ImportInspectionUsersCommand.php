<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImportInspectionUsersCommand extends Command
{
    protected $signature = 'users:import-inspection
        {file : Chemin absolu du fichier CSV nominatif}
        {--execute : Créer réellement les comptes}
        {--confirm= : Phrase obligatoire IMPORTER-AGENTS-INSPECTION}
        {--force : Autoriser l’exécution en production}
        {--credentials= : Chemin du rapport CSV des accès temporaires}';

    protected $description = 'Contrôle puis importe les agents de l’Inspection des Services depuis un CSV privé';

    /** @var list<string> */
    private const REQUIRED_HEADERS = [
        'validation', 'intercom', 'nom_complet', 'fonction', 'role_systeme',
        'code_structure', 'responsable', 'telephones', 'email',
    ];

    public function handle(): int
    {
        $path = $this->absolutePath((string) $this->argument('file'));
        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Fichier CSV introuvable ou illisible : {$path}");

            return self::FAILURE;
        }

        try {
            [$headers, $rows] = $this->readCsv($path);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, $headers));
        if ($missingHeaders !== []) {
            $this->error('Colonnes obligatoires absentes : '.implode(', ', $missingHeaders));

            return self::FAILURE;
        }

        $departments = Department::query()->whereIn('code', collect($rows)->pluck('code_structure')->filter()->unique())->get()->keyBy('code');
        $roles = Role::query()->whereIn('slug', collect($rows)->pluck('role_systeme')->filter()->unique())->get()->keyBy('slug');
        $existingEmails = User::withTrashed()->whereIn('email', collect($rows)->pluck('email')->filter()->map(fn ($email) => strtolower((string) $email))->unique())->pluck('email')->map(fn ($email) => strtolower((string) $email))->flip();

        $seenEmails = [];
        $errors = [];
        $validRows = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $code = strtoupper(trim((string) ($row['code_structure'] ?? '')));
            $roleSlug = trim((string) ($row['role_systeme'] ?? ''));

            if (mb_strtolower(trim((string) ($row['validation'] ?? ''))) !== 'validé') {
                $errors[] = "Ligne {$line} : statut non validé.";
            }
            if (trim((string) ($row['nom_complet'] ?? '')) === '') {
                $errors[] = "Ligne {$line} : nom absent.";
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Ligne {$line} : courriel invalide ({$email}).";
            } elseif (isset($seenEmails[$email])) {
                $errors[] = "Ligne {$line} : courriel dupliqué dans le fichier ({$email}).";
            } elseif (isset($existingEmails[$email])) {
                $errors[] = "Ligne {$line} : compte déjà présent dans la base ({$email}).";
            }
            $seenEmails[$email] = true;

            $department = $departments->get($code);
            if ($department === null) {
                $errors[] = "Ligne {$line} : structure inconnue ({$code}).";
            }
            $role = $roles->get($roleSlug);
            if ($role === null || ! $role->active) {
                $errors[] = "Ligne {$line} : rôle institutionnel inconnu ou inactif ({$roleSlug}).";
            }
            if (mb_strlen((string) ($row['intercom'] ?? '')) > 64) {
                $errors[] = "Ligne {$line} : intercom trop long.";
            }
            if (mb_strlen((string) ($row['telephones'] ?? '')) > 32) {
                $errors[] = "Ligne {$line} : téléphone trop long.";
            }

            $row['email'] = $email;
            $row['code_structure'] = $code;
            $row['_department'] = $department;
            $row['_role'] = $role;
            $validRows[] = $row;
        }

        $this->table(['Contrôle', 'Valeur'], [
            ['Lignes lues', count($rows)],
            ['Structures concernées', $departments->count()],
            ['Responsables proposés', collect($rows)->filter(fn ($row) => $this->isYes($row['responsable'] ?? null))->count()],
            ['Erreurs bloquantes', count($errors)],
            ['Mode', $this->option('execute') ? 'EXÉCUTION' : 'SIMULATION'],
        ]);

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }
            $this->error('Import annulé : corrigez toutes les erreurs puis relancez la simulation.');

            return self::FAILURE;
        }

        if (! $this->option('execute')) {
            $this->info('Simulation réussie : aucune donnée n’a été modifiée.');

            return self::SUCCESS;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('L’option --force est obligatoire en production.');

            return self::FAILURE;
        }
        if ($this->option('confirm') !== 'IMPORTER-AGENTS-INSPECTION') {
            $this->error('Confirmation incorrecte. Utilisez --confirm=IMPORTER-AGENTS-INSPECTION.');

            return self::FAILURE;
        }

        $approverId = User::query()->whereHas('institutionalRole', fn ($query) => $query->where('slug', 'super_admin'))->value('id');
        if ($approverId === null) {
            throw new RuntimeException('Aucun super administrateur actif ne peut approuver l’import.');
        }

        $credentials = [];
        $credentialsPath = $this->credentialsPath();
        try {
            DB::transaction(function () use ($validRows, $approverId, $credentialsPath, &$credentials): void {
                foreach ($validRows as $row) {
                    $password = Str::password(20, true, true, true, false);
                    $user = User::query()->create([
                        'name' => trim((string) $row['nom_complet']),
                        'prenom' => null,
                        'email' => $row['email'],
                        'password' => Hash::make($password),
                        'telephone' => $this->nullable($row['telephones'] ?? null),
                        'intercom' => $this->nullable($row['intercom'] ?? null),
                        'matricule' => null,
                        'fonction' => $this->nullable($row['fonction'] ?? null),
                        'position' => $this->nullable($row['fonction'] ?? null),
                        'department_id' => $row['_department']->id,
                        'role_id' => $row['_role']->id,
                        'role' => $row['_role']->slug,
                        'active' => true,
                        'must_change_password' => true,
                        'password_changed_at' => null,
                        'approval_status' => User::APPROVAL_STATUS_APPROVED,
                        'approved_at' => now(),
                        'approved_by' => $approverId,
                    ]);

                    if ($this->isYes($row['responsable'] ?? null)) {
                        $row['_department']->update(['supervisor_user_id' => $user->id]);
                    }

                    $credentials[] = [$user->email, $password, $row['_department']->code, 'Oui'];
                }

                $this->writeCredentials($credentialsPath, $credentials);
            });
        } catch (Throwable $exception) {
            @unlink($credentialsPath);
            throw $exception;
        }

        $this->info(count($credentials).' comptes créés avec succès.');
        $this->warn("Rapport confidentiel créé : {$credentialsPath}");
        $this->warn('Conservez ce fichier hors de GitHub et supprimez-le après remise sécurisée des accès.');

        return self::SUCCESS;
    }

    /** @return array{0:list<string>,1:list<array<string,string>>} */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Impossible d’ouvrir le fichier CSV.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new RuntimeException('Le fichier CSV est vide.');
        }
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);
        $rawHeaders = fgetcsv($handle, 0, $delimiter);
        if ($rawHeaders === false) {
            fclose($handle);
            throw new RuntimeException('En-tête CSV illisible.');
        }
        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $rawHeaders);
        $rows = [];
        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $values = array_pad(array_slice($values, 0, count($headers)), count($headers), '');
            $rows[] = array_combine($headers, $values);
        }
        fclose($handle);

        return [$headers, $rows];
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $normalized = Str::of($header)->trim()->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();

        return match ($normalized) {
            'numero_intercom' => 'intercom',
            'telephone_s', 'telephone' => 'telephones',
            'courriel' => 'email',
            default => $normalized,
        };
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isYes(mixed $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['oui', 'yes', '1', 'true'], true);
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
            ? $path
            : base_path($path);
    }

    private function credentialsPath(): string
    {
        $requested = trim((string) $this->option('credentials'));

        return $requested !== '' ? $this->absolutePath($requested) : storage_path('app/private/imports/acces-agents-'.now()->format('Ymd-His').'.csv');
    }

    /** @param list<array{0:string,1:string,2:string,3:string}> $credentials */
    private function writeCredentials(string $path, array $credentials): void
    {
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException("Impossible de créer le dossier confidentiel : {$directory}");
        }
        $handle = fopen($path, 'xb');
        if ($handle === false) {
            throw new RuntimeException("Le rapport existe déjà ou ne peut pas être créé : {$path}");
        }
        fputcsv($handle, ['email', 'mot_de_passe_temporaire', 'structure', 'changement_obligatoire'], ';');
        foreach ($credentials as $credential) {
            fputcsv($handle, $credential, ';');
        }
        fclose($handle);
        @chmod($path, 0600);
    }
}
