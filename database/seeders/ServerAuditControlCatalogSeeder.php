<?php

namespace Database\Seeders;

use App\Models\ServerAuditControlDefinition;
use Illuminate\Database\Seeder;

class ServerAuditControlCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            $references = $item['references'];
            unset($item['references']);
            $definition = ServerAuditControlDefinition::query()->updateOrCreate(['code' => $item['code']], $item);
            $definition->references()->delete();
            $definition->references()->createMany($references);
        }
    }

    private function catalog(): array
    {
        $cis = fn (string $document, string $section, string $summary) => [
            'authority' => 'CIS', 'document_title' => $document, 'document_version' => null,
            'section' => $section, 'provision_reference' => $section, 'requirement_summary' => $summary, 'is_primary' => true,
        ];
        $anssi = fn (string $provision, string $summary) => [
            'authority' => 'ANSSI', 'document_title' => 'Guide d’hygiène informatique', 'document_version' => 'v1.4',
            'provision_reference' => $provision, 'requirement_summary' => $summary, 'is_primary' => false,
        ];
        $make = fn (string $code, string $title, string $category, string $criticality, array $applicability, string $expected, string $risk, string $treatment, array $references) => compact(
            'code', 'title', 'category', 'criticality', 'applicability', 'references'
        ) + [
            'version' => 1, 'control_objective' => $title, 'default_expected' => $expected,
            'risk_description' => $risk, 'treatment_guidance' => $treatment, 'active' => true,
        ];

        return [
            $make('WIN-AD-ADMINS', 'Appartenance excessive aux groupes privilégiés AD', 'Identités et privilèges', 'critical', ['platforms' => ['windows'], 'products' => ['active-directory']], 'Groupes privilégiés limités aux comptes nominatifs justifiés', 'Une compromission d’un compte privilégié peut entraîner la prise de contrôle du domaine.', 'Réviser les appartenances, retirer les comptes injustifiés et appliquer le moindre privilège.', [$anssi('46', 'Limiter strictement les privilèges administratifs.')]),
            $make('WIN-PASSWORD-POLICY', 'Politique de mots de passe Windows insuffisante', 'Authentification', 'high', ['platforms' => ['windows']], 'Longueur minimale 14 et historique 24', 'Une politique faible facilite la compromission des comptes.', 'Configurer la politique approuvée par GPO et organiser son déploiement.', [$cis('CIS Microsoft Windows Server Benchmark', '2.3.1.4 / 2.3.1.7', 'Renforcer longueur et historique des mots de passe.'), $anssi('56', 'Appliquer une politique de mots de passe robuste.')]),
            $make('WIN-SMB1', 'SMBv1 activé', 'Services réseau', 'high', ['platforms' => ['windows']], 'SMBv1 désactivé', 'Un protocole obsolète accroît l’exposition aux attaques réseau.', 'Désactiver SMBv1 après inventaire et validation des dépendances.', [$anssi('37', 'Désactiver les services et protocoles inutiles ou obsolètes.')]),
            $make('WIN-AUDITPOL', 'Journalisation Windows insuffisante', 'Journalisation', 'high', ['platforms' => ['windows']], 'Catégories d’audit requises activées', 'L’absence de traces empêche la détection et l’investigation.', 'Activer la stratégie d’audit et centraliser les événements.', [$anssi('102', 'Mettre en œuvre une journalisation exploitable et centralisée.')]),
            $make('LINUX-ROOT-SSH', 'Connexion SSH directe de root autorisée', 'Accès distant', 'critical', ['platforms' => ['linux', 'unix']], 'PermitRootLogin no', 'Une compromission donne directement les privilèges les plus élevés.', 'Configurer PermitRootLogin no, utiliser des comptes nominatifs et sudo.', [$cis('CIS Ubuntu Linux Benchmark', '5.2.6', 'Interdire la connexion SSH directe de root.'), $anssi('141', 'Durcir les accès d’administration distants.')]),
            $make('LINUX-SSH-PASSWORD', 'Authentification SSH par mot de passe autorisée', 'Accès distant', 'high', ['platforms' => ['linux', 'unix']], 'PasswordAuthentication no', 'Les mots de passe exposent le service aux attaques par force brute.', 'Déployer les clés SSH, vérifier les accès de secours puis désactiver PasswordAuthentication.', [$cis('CIS Ubuntu Linux Benchmark', '5.2.8', 'Privilégier une authentification SSH forte.')]),
            $make('LINUX-SUDO-NOPASSWD', 'Privilèges sudo sans authentification', 'Privilèges', 'high', ['platforms' => ['linux', 'unix']], 'Aucune règle NOPASSWD injustifiée', 'Une règle trop permissive facilite l’élévation de privilèges.', 'Supprimer les règles NOPASSWD non justifiées et documenter les exceptions.', [$cis('CIS Ubuntu Linux Benchmark', '6.5.2', 'Encadrer les usages de sudo sans mot de passe.')]),
            $make('LINUX-AUDITD', 'Auditd absent ou inactif', 'Journalisation', 'high', ['platforms' => ['linux']], 'auditd actif', 'L’absence de journalisation réduit la capacité de détection et de preuve.', 'Installer, configurer et superviser auditd, puis centraliser les journaux.', [$cis('CIS Ubuntu Linux Benchmark', '4.1.1', 'Activer le service d’audit système.'), $anssi('102', 'Assurer la traçabilité des événements de sécurité.')]),
            $make('MYSQL-ROOT-WILDCARD', 'Compte MySQL root accessible depuis toute adresse', 'Bases de données', 'critical', ['products' => ['mysql', 'mariadb']], 'Aucun root@% ou root@0.0.0.0', 'Un compte administratif exposé augmente fortement le risque de compromission.', 'Supprimer les comptes génériques et restreindre les origines autorisées.', [$anssi('44', 'Restreindre l’exposition et les accès aux services d’administration.')]),
            $make('MYSQL-TLS', 'Transport sécurisé MySQL non obligatoire', 'Chiffrement', 'high', ['products' => ['mysql', 'mariadb']], 'require_secure_transport=ON', 'Des identifiants et données peuvent être interceptés en transit.', 'Déployer les certificats, activer TLS puis require_secure_transport.', [$anssi('45', 'Protéger les flux d’administration et de données sensibles.')]),
            $make('MSSQL-SA', 'Compte SQL Server sa actif ou insuffisamment protégé', 'Bases de données', 'critical', ['products' => ['sql-server']], 'Compte sa désactivé et politique de mot de passe appliquée', 'Le compte universel sa constitue une cible privilégiée.', 'Désactiver sa, utiliser des comptes nominatifs et privilégier l’authentification Windows.', [$cis('CIS Microsoft SQL Server 2019 Benchmark', '1.1.2', 'Désactiver le compte sa lorsqu’il n’est pas requis.'), $anssi('44', 'Restreindre les comptes et accès administratifs.')]),
            $make('MSSQL-XPCMDSHELL', 'Fonctionnalité xp_cmdshell activée', 'Bases de données', 'high', ['products' => ['sql-server']], 'xp_cmdshell run_value = 0', 'Cette fonction permet l’exécution de commandes système depuis le moteur SQL.', 'Désactiver xp_cmdshell sauf exception formellement justifiée et compensée.', [$cis('CIS Microsoft SQL Server 2019 Benchmark', '1.4.1', 'Désactiver xp_cmdshell.')]),
            $make('NMAP-UNEXPECTED-PORT', 'Service réseau non attendu exposé', 'Exposition réseau', 'high', ['asset_types' => ['scanner', 'server'], 'platforms' => ['*']], 'Uniquement les services autorisés sont exposés', 'Un service non maîtrisé augmente la surface d’attaque.', 'Faire qualifier le service par l’auditeur puis le justifier, le durcir ou le filtrer.', [$anssi('37', 'Réduire les services exposés au strict nécessaire.')]),
        ];
    }
}
