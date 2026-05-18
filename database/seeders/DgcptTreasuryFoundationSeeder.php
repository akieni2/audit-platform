<?php

namespace Database\Seeders;

use App\Domain\Dgcpt\Enums\TreasuryEntityType;
use App\Models\Dgcpt\AuditDomain;
use App\Models\Dgcpt\AuditTemplate;
use App\Models\Dgcpt\TreasuryEntity;
use App\Models\Dgcpt\TreasuryService;
use Illuminate\Database\Seeder;

class DgcptTreasuryFoundationSeeder extends Seeder
{
    /**
     * Référentiel national DGCPT : entités, services types, domaines d'audit.
     */
    public function run(): void
    {
        $national = TreasuryEntity::query()->updateOrCreate(
            ['code' => 'DGCPT'],
            [
                'name' => 'Direction Générale du Contrôle des Marchés publics et des Engagements financiers',
                'entity_type' => TreasuryEntityType::National->value,
                'province' => null,
                'country' => 'GA',
                'active' => true,
            ],
        );

        $provinces = [
            ['code' => 'TP-EST', 'name' => 'Trésorerie Provinciale de l\'Estuaire', 'province' => 'Estuaire', 'city' => 'Libreville'],
            ['code' => 'TP-HO', 'name' => 'Trésorerie Provinciale du Haut-Ogooué', 'province' => 'Haut-Ogooué', 'city' => 'Franceville'],
            ['code' => 'TP-MO', 'name' => 'Trésorerie Provinciale du Moyen-Ogooué', 'province' => 'Moyen-Ogooué', 'city' => 'Lambaréné'],
            ['code' => 'TP-NG', 'name' => 'Trésorerie Provinciale de la Ngounié', 'province' => 'Ngounié', 'city' => 'Mouila'],
            ['code' => 'TP-NY', 'name' => 'Trésorerie Provinciale de la Nyanga', 'province' => 'Nyanga', 'city' => 'Tchibanga'],
            ['code' => 'TP-OI', 'name' => 'Trésorerie Provinciale de l\'Ogooué-Ivindo', 'province' => 'Ogooué-Ivindo', 'city' => 'Makokou'],
            ['code' => 'TP-OL', 'name' => 'Trésorerie Provinciale de l\'Ogooué-Lolo', 'province' => 'Ogooué-Lolo', 'city' => 'Koulamoutou'],
            ['code' => 'TP-OM', 'name' => 'Trésorerie Provinciale de l\'Ogooué-Maritime', 'province' => 'Ogooué-Maritime', 'city' => 'Port-Gentil'],
            ['code' => 'TP-WN', 'name' => 'Trésorerie Provinciale du Woleu-Ntem', 'province' => 'Woleu-Ntem', 'city' => 'Oyem'],
        ];

        foreach ($provinces as $province) {
            $tp = TreasuryEntity::query()->updateOrCreate(
                ['code' => $province['code']],
                [
                    'name' => $province['name'],
                    'entity_type' => TreasuryEntityType::Provincial->value,
                    'province' => $province['province'],
                    'country' => 'GA',
                    'parent_entity_id' => $national->id,
                    'active' => true,
                    'metadata' => ['chef_lieu' => $province['city']],
                ],
            );

            $this->seedStandardServices($tp);
        }

        TreasuryEntity::query()->updateOrCreate(
            ['code' => 'TP-PARIS'],
            [
                'name' => 'Trésorerie du Gabon à Paris',
                'entity_type' => TreasuryEntityType::International->value,
                'province' => null,
                'country' => 'FR',
                'parent_entity_id' => $national->id,
                'active' => true,
            ],
        );

        $domains = [
            ['code' => 'AUDIT_SI', 'name' => 'Audit SI'],
            ['code' => 'CTRL_INTERNE', 'name' => 'Contrôle interne'],
            ['code' => 'CYBER', 'name' => 'Cybersécurité'],
            ['code' => 'COMPTA', 'name' => 'Comptabilité'],
            ['code' => 'GOUV', 'name' => 'Gouvernance'],
            ['code' => 'PCA', 'name' => 'Continuité d\'activité'],
            ['code' => 'ACCES', 'name' => 'Gestion des accès'],
            ['code' => 'RESEAU', 'name' => 'Infrastructure réseau'],
            ['code' => 'SAUVEGARDE', 'name' => 'Sauvegarde et PRA'],
        ];

        foreach ($domains as $domain) {
            AuditDomain::query()->updateOrCreate(
                ['code' => $domain['code']],
                [
                    'name' => $domain['name'],
                    'description' => 'Domaine d\'audit DGCPT — '.$domain['name'],
                    'active' => true,
                ],
            );
        }

        $auditSi = AuditDomain::query()->where('code', 'AUDIT_SI')->first();

        AuditTemplate::query()->updateOrCreate(
            ['code' => 'TPL_AUDIT_SI_TP'],
            [
                'name' => 'Audit SI Trésorerie Provinciale',
                'description' => 'Template national exécutable dans toutes les TP (ex. TP Lambaréné, TP Port-Gentil).',
                'audit_domain_id' => $auditSi?->id,
                'applicable_entity_types' => [
                    TreasuryEntityType::Provincial->value,
                    TreasuryEntityType::Departmental->value,
                ],
                'active' => true,
                'metadata' => [
                    'example_missions' => ['TP Lambaréné', 'TP Port-Gentil', 'TP Oyem'],
                ],
            ],
        );
    }

    private function seedStandardServices(TreasuryEntity $entity): void
    {
        $catalog = [
            ['code' => 'SVC-IT', 'name' => 'Informatique', 'service_type' => 'informatique'],
            ['code' => 'SVC-COMPTA', 'name' => 'Comptabilité', 'service_type' => 'comptabilite'],
            ['code' => 'SVC-REC', 'name' => 'Recettes', 'service_type' => 'recettes'],
            ['code' => 'SVC-DEP', 'name' => 'Dépenses', 'service_type' => 'depenses'],
            ['code' => 'SVC-CTRL', 'name' => 'Contrôle', 'service_type' => 'controle'],
            ['code' => 'SVC-RECOUV', 'name' => 'Recouvrement', 'service_type' => 'recouvrement'],
            ['code' => 'SVC-RESEAU', 'name' => 'Réseau / SI', 'service_type' => 'reseau_si'],
            ['code' => 'SVC-ADMIN', 'name' => 'Administratif', 'service_type' => 'administratif'],
        ];

        foreach ($catalog as $service) {
            TreasuryService::query()->updateOrCreate(
                [
                    'treasury_entity_id' => $entity->id,
                    'code' => $service['code'],
                ],
                [
                    'name' => $service['name'],
                    'service_type' => $service['service_type'],
                    'active' => true,
                ],
            );
        }
    }
}
