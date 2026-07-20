<?php

namespace Database\Seeders;

use App\Models\ControlLibrary;
use App\Models\ControlMeasure;
use App\Models\MethodologyCategory;
use App\Models\MethodologyControl;
use App\Models\MethodologyMapping;
use App\Models\MethodologyRequirement;
use App\Models\MethodologyTemplate;
use App\Models\Taxonomy;
use App\Models\TaxonomyTerm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DgcptReferentialCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $riskTaxonomy = $this->seedRiskTaxonomy();

        foreach ($this->referentials() as $referential) {
            $template = MethodologyTemplate::query()->updateOrCreate(
                ['slug' => $referential['slug'], 'version' => 1],
                [
                    'name' => $referential['name'],
                    'framework_key' => $referential['framework_key'],
                    'code' => $referential['code'],
                    'description' => $referential['description'],
                    'active' => true,
                    'is_system' => true,
                    'is_global' => true,
                    'lifecycle_status' => MethodologyTemplate::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'metadata' => [
                        'institutional_source' => 'Note de service DGCPT - Adoption des normes internationales IT',
                        'scope' => $referential['scope'],
                        'audit_procedure' => $referential['procedure'],
                        'question_bank' => $referential['questions'],
                        'risk_families' => $referential['risk_families'],
                        'deliverable_library' => collect($referential['procedure'])
                            ->flatMap(fn (array $stage) => $stage['deliverables'])
                            ->unique()
                            ->values()
                            ->all(),
                    ],
                ]
            );

            $library = ControlLibrary::query()->updateOrCreate(
                ['slug' => $referential['slug'].'-controls'],
                [
                    'methodology_template_id' => $template->id,
                    'name' => 'Bibliothèque '.$referential['name'],
                    'description' => 'Questions, livrables et mesures de contrôle rattachés au référentiel '.$referential['name'].'.',
                    'visibility_scope' => 'institutional',
                    'active' => true,
                    'metadata' => [
                        'question_bank' => $referential['questions'],
                        'deliverables' => collect($referential['procedure'])->pluck('deliverables')->flatten()->values()->all(),
                    ],
                ]
            );

            foreach ($referential['procedure'] as $index => $stage) {
                $category = MethodologyCategory::query()->updateOrCreate(
                    [
                        'methodology_template_id' => $template->id,
                        'code' => $stage['code'],
                    ],
                    [
                        'name' => $stage['name'],
                        'description' => $stage['objective'],
                        'sort_order' => $index + 1,
                        'metadata' => [
                            'deliverables' => $stage['deliverables'],
                            'expected_outcome' => $stage['expected_outcome'],
                        ],
                    ]
                );

                $control = MethodologyControl::query()->updateOrCreate(
                    [
                        'methodology_template_id' => $template->id,
                        'control_reference' => $stage['code'],
                    ],
                    [
                        'methodology_category_id' => $category->id,
                        'title' => $stage['name'],
                        'description' => $stage['objective'],
                        'control_type' => 'audit_stage',
                        'criticality' => $stage['criticality'],
                        'default_workflow_stage_code' => $stage['code'],
                        'control_objective' => $stage['expected_outcome'],
                        'metadata' => [
                            'deliverables' => $stage['deliverables'],
                            'question_topics' => $stage['question_topics'],
                        ],
                    ]
                );

                MethodologyRequirement::query()->updateOrCreate(
                    [
                        'methodology_template_id' => $template->id,
                        'requirement_reference' => $stage['code'].'-LIVRABLES',
                    ],
                    [
                        'methodology_category_id' => $category->id,
                        'methodology_control_id' => $control->id,
                        'title' => 'Livrables obligatoires - '.$stage['name'],
                        'description' => implode(', ', $stage['deliverables']),
                        'status' => 'active',
                        'applicability_scope' => 'Toute mission utilisant '.$referential['name'],
                        'metadata' => ['deliverables' => $stage['deliverables']],
                    ]
                );

                $measure = ControlMeasure::query()->updateOrCreate(
                    [
                        'control_library_id' => $library->id,
                        'code' => $referential['code'].'-'.$stage['code'],
                    ],
                    [
                        'methodology_control_id' => $control->id,
                        'title' => 'Contrôle de complétude - '.$stage['name'],
                        'description' => 'Vérifier que les activités, preuves et livrables attendus sont produits, validés et traçables.',
                        'execution_frequency' => 'Chaque mission',
                        'owner_role' => 'Administrateur entité / Chef de mission',
                        'maturity_level' => 2,
                        'metadata' => [
                            'deliverables' => $stage['deliverables'],
                            'questions' => collect($referential['questions'])
                                ->whereIn('topic', $stage['question_topics'])
                                ->values()
                                ->all(),
                        ],
                    ]
                );

                MethodologyMapping::query()->updateOrCreate(
                    [
                        'methodology_template_id' => $template->id,
                        'methodology_control_id' => $control->id,
                        'control_library_id' => $library->id,
                        'control_measure_id' => $measure->id,
                        'mapping_type' => 'procedure_stage',
                    ],
                    [
                        'risk_category' => $stage['risk_category'],
                        'mapping_payload' => [
                            'stage' => $stage,
                            'referential' => $referential['framework_key'],
                        ],
                    ]
                );
            }

            foreach ($referential['risk_families'] as $riskFamily) {
                $term = TaxonomyTerm::query()
                    ->where('taxonomy_id', $riskTaxonomy->id)
                    ->where('code', $riskFamily['taxonomy_code'])
                    ->first();

                MethodologyMapping::query()->updateOrCreate(
                    [
                        'methodology_template_id' => $template->id,
                        'taxonomy_term_id' => $term?->id,
                        'mapping_type' => 'risk_taxonomy',
                        'risk_category' => $riskFamily['label'],
                    ],
                    [
                        'mapping_payload' => [
                            'source_label' => $riskFamily['label'],
                            'taxonomy_code' => $riskFamily['taxonomy_code'],
                            'aliases' => $riskFamily['aliases'],
                        ],
                    ]
                );
            }
        }
    }

    private function seedRiskTaxonomy(): Taxonomy
    {
        $taxonomy = Taxonomy::query()->updateOrCreate(
            ['slug' => 'dgcpt-risk-taxonomy'],
            [
                'name' => 'Taxonomie commune des risques DGCPT',
                'taxonomy_type' => 'risk',
                'description' => 'Langage commun permettant de consolider les risques issus de référentiels différents.',
                'active' => true,
                'is_national' => true,
                'metadata' => [
                    'purpose' => 'Consolidation top management',
                    'harmonization_level' => 'national',
                ],
            ]
        );

        foreach ($this->taxonomyTerms() as $index => $term) {
            TaxonomyTerm::query()->updateOrCreate(
                ['taxonomy_id' => $taxonomy->id, 'code' => $term['code']],
                [
                    'name' => $term['name'],
                    'description' => $term['description'],
                    'alias_terms' => $term['aliases'],
                    'sort_order' => $index + 1,
                    'metadata' => ['dashboard_bucket' => $term['bucket']],
                ]
            );
        }

        return $taxonomy;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function taxonomyTerms(): array
    {
        return [
            ['code' => 'GOV', 'name' => 'Gouvernance, stratégie et conformité', 'bucket' => 'governance', 'description' => 'Risques liés au pilotage, aux politiques, responsabilités, exigences réglementaires et conformité.', 'aliases' => ['gouvernance', 'conformité', 'politique', 'pilotage']],
            ['code' => 'FIN', 'name' => 'Opérations financières et comptables', 'bucket' => 'finance', 'description' => 'Risques relatifs aux opérations comptables, trésorerie, recettes, dépenses et reporting financier.', 'aliases' => ['comptable', 'trésor', 'poste comptable', 'finance']],
            ['code' => 'SI', 'name' => 'Systèmes d’information et cybersécurité', 'bucket' => 'it', 'description' => 'Risques IT, cyber, applications, infrastructures, données, accès et continuité numérique.', 'aliases' => ['cyber', 'IT', 'SI', 'sécurité information']],
            ['code' => 'OPS', 'name' => 'Processus, performance et qualité de service', 'bucket' => 'operations', 'description' => 'Risques liés à l’efficacité opérationnelle, la qualité de service, les délais et la performance.', 'aliases' => ['processus', 'performance', 'qualité', 'service']],
            ['code' => 'CTRL', 'name' => 'Contrôle interne et maîtrise des risques', 'bucket' => 'control', 'description' => 'Risques associés au dispositif de contrôle interne, plans de maîtrise, suivi et assurance.', 'aliases' => ['contrôle interne', 'maîtrise des risques', 'assurance']],
            ['code' => 'DATA', 'name' => 'Données, traçabilité et reporting', 'bucket' => 'data', 'description' => 'Risques relatifs à la qualité des données, traçabilité, preuves, reporting et auditabilité.', 'aliases' => ['données', 'preuve', 'reporting', 'traçabilité']],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function referentials(): array
    {
        $standardProcedure = $this->standardProcedure();

        return [
            $this->referential('isaca-audit-si', 'Normes d’audit ISACA', 'ISACA', 'ISACA', 'Lignes directrices pour l’audit des systèmes de management et IT assurance.', 'Audit SI, assurance et reporting des systèmes d’information.', $this->itProcedure(), ['SI', 'GOV', 'DATA']),
            $this->referential('itaf', 'ITAF', 'ITAF', 'ITAF', 'Cadre d’assurance et d’audit des technologies de l’information.', 'Planification, conduite et reporting des missions d’assurance SI.', $this->itProcedure(), ['SI', 'GOV', 'CTRL']),
            $this->referential('iso-19011-2018', 'ISO 19011:2018', 'ISO19011', 'ISO 19011', 'Lignes directrices pour l’audit des systèmes de management.', 'Méthode générique d’audit applicable à plusieurs systèmes de management.', $standardProcedure, ['GOV', 'CTRL', 'OPS']),
            $this->referential('iso-iec-38500', 'ISO/IEC 38500', 'ISO38500', 'ISO/IEC 38500', 'Gouvernance des technologies de l’information.', 'Aligner IT et métier, optimiser la valeur et gérer les risques.', $standardProcedure, ['GOV', 'SI', 'OPS']),
            $this->referential('cobit', 'COBIT', 'COBIT', 'COBIT', 'Cadre de gouvernance et de gestion des technologies de l’information.', 'Objectifs de gouvernance et de gestion IT, de EDM à MEA.', $this->itProcedure(), ['GOV', 'SI', 'CTRL']),
            $this->referential('iso-iec-31000', 'ISO/IEC 31000', 'ISO31000', 'ISO/IEC 31000', 'Principes et lignes directrices pour le management du risque.', 'Identifier, analyser, évaluer et traiter les incertitudes.', $standardProcedure, ['CTRL', 'GOV', 'OPS']),
            $this->referential('coso-erm-2017', 'COSO ERM 2017', 'COSOERM', 'COSO ERM', 'Référentiel de gestion des risques d’entreprise.', 'Intégrer les risques à la stratégie, à la performance et au reporting.', $standardProcedure, ['GOV', 'CTRL', 'DATA']),
            $this->referential('iso-iec-20000-itil', 'ISO/IEC 20000 / ITIL', 'ISO20000_ITIL', 'ISO/IEC 20000, ITIL', 'Management des services IT et bonnes pratiques ITIL.', 'Optimiser la qualité, la disponibilité et l’amélioration continue des services IT.', $this->itProcedure(), ['SI', 'OPS', 'DATA']),
            $this->referential('iso-iec-12207', 'ISO/IEC 12207', 'ISO12207', 'ISO/IEC 12207', 'Ingénierie des systèmes et du logiciel - processus du cycle de vie logiciel.', 'Encadrer le développement, la maintenance et l’assurance qualité logicielle.', $this->itProcedure(), ['SI', 'DATA', 'OPS']),
            $this->referential('iso-iec-27000', 'Famille ISO/IEC 27000', 'ISO27000', 'ISO/IEC 27000', 'Management de la sécurité de l’information.', 'Protéger les informations sensibles et améliorer le SMSI.', $this->itProcedure(), ['SI', 'GOV', 'DATA']),
            $this->referential('nist-csf', 'NIST Cybersecurity Framework', 'NIST_CSF', 'NIST CSF', 'Cadre NIST pour la cybersécurité.', 'Garantir confidentialité, intégrité, disponibilité et gestion des risques cyber.', $this->itProcedure(), ['SI', 'DATA', 'CTRL']),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $procedure
     * @param  array<int, string>  $taxonomyCodes
     * @return array<string, mixed>
     */
    private function referential(string $slug, string $name, string $frameworkKey, string $code, string $description, string $scope, array $procedure, array $taxonomyCodes): array
    {
        return [
            'slug' => $slug,
            'name' => $name,
            'framework_key' => $frameworkKey,
            'code' => $code,
            'description' => $description,
            'scope' => $scope,
            'procedure' => $procedure,
            'questions' => $this->questionsFor($frameworkKey, $taxonomyCodes),
            'risk_families' => collect($taxonomyCodes)->map(fn (string $code) => [
                'label' => $this->taxonomyLabel($code),
                'taxonomy_code' => $code,
                'aliases' => [Str::lower($frameworkKey), Str::lower($this->taxonomyLabel($code))],
            ])->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function itProcedure(): array
    {
        return [
            ['code' => 'LANCEMENT', 'name' => 'Lancement', 'objective' => 'Formaliser le périmètre, les objectifs, les responsabilités et l’autorisation de mission.', 'expected_outcome' => 'Mission cadrée, mandatée et communiquée aux parties prenantes.', 'deliverables' => ['Lettre de cadrage', 'Lettre de mission', 'Ordre de mission'], 'question_topics' => ['gouvernance', 'périmètre'], 'risk_category' => 'Gouvernance', 'criticality' => 'high'],
            ['code' => 'PREPARATION', 'name' => 'Préparation et collecte', 'objective' => 'Préparer questionnaires, plan d’audit, checklist et revue des cartographies passées.', 'expected_outcome' => 'Dispositif de collecte prêt et aligné sur le référentiel choisi.', 'deliverables' => ['Plan d’audit', 'Questionnaires', 'Checklist', 'Revue des cartographies antérieures'], 'question_topics' => ['contrôles', 'données', 'historique'], 'risk_category' => 'Contrôle interne', 'criticality' => 'medium'],
            ['code' => 'TERRAIN', 'name' => 'Terrain et programme de travail', 'objective' => 'Conduire les entretiens, tests, observations et collectes de preuves auprès des audités.', 'expected_outcome' => 'Constats documentés et preuves rattachées aux objectifs d’audit.', 'deliverables' => ['Programme de travail', 'Feuilles de tests', 'Procès-verbaux d’entretien', 'Dossier de preuves'], 'question_topics' => ['opérations', 'preuves'], 'risk_category' => 'Processus', 'criticality' => 'high'],
            ['code' => 'CARTOGRAPHIE', 'name' => 'Cartographie des risques', 'objective' => 'Qualifier les risques, évaluer criticité inhérente et résiduelle, puis harmoniser vers la taxonomie DGCPT.', 'expected_outcome' => 'Cartographie des risques exploitable par l’entité et consolidable au niveau national.', 'deliverables' => ['Registre des risques', 'Matrice de criticité', 'Cartographie harmonisée DGCPT'], 'question_topics' => ['risques', 'traitement'], 'risk_category' => 'Maîtrise des risques', 'criticality' => 'critical'],
            ['code' => 'REPORTING', 'name' => 'Reporting, recommandations et suivi', 'objective' => 'Restituer les résultats, recommander les actions et suivre leur mise en œuvre.', 'expected_outcome' => 'Rapport validé, recommandations priorisées et plan de suivi activé.', 'deliverables' => ['Rapport d’audit', 'Plan d’actions', 'Compte rendu de restitution', 'Tableau de suivi'], 'question_topics' => ['reporting', 'suivi'], 'risk_category' => 'Reporting', 'criticality' => 'high'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function standardProcedure(): array
    {
        return [
            ['code' => 'CADRAGE', 'name' => 'Cadrage et planification', 'objective' => 'Définir périmètre, objectifs, méthode, critères et calendrier.', 'expected_outcome' => 'Mission planifiée et validée.', 'deliverables' => ['Note de cadrage', 'Plan de mission', 'Calendrier d’audit'], 'question_topics' => ['gouvernance', 'périmètre'], 'risk_category' => 'Gouvernance', 'criticality' => 'high'],
            ['code' => 'REFERENTIEL', 'name' => 'Adaptation du référentiel', 'objective' => 'Adapter les exigences du référentiel au contexte de l’entité auditée.', 'expected_outcome' => 'Critères d’audit contextualisés.', 'deliverables' => ['Matrice exigences / contexte', 'Checklist adaptée', 'Questionnaire'], 'question_topics' => ['contrôles', 'conformité'], 'risk_category' => 'Conformité', 'criticality' => 'medium'],
            ['code' => 'EXECUTION', 'name' => 'Travaux d’audit', 'objective' => 'Réaliser entretiens, tests, analyses documentaires et collecte de preuves.', 'expected_outcome' => 'Constats et preuves qualifiés.', 'deliverables' => ['Programme de travail', 'Dossier de preuves', 'Constats provisoires'], 'question_topics' => ['opérations', 'preuves'], 'risk_category' => 'Processus', 'criticality' => 'high'],
            ['code' => 'RISQUES', 'name' => 'Evaluation et harmonisation des risques', 'objective' => 'Identifier, évaluer, prioriser et mapper les risques vers la taxonomie commune DGCPT.', 'expected_outcome' => 'Registre harmonisé et consolidable.', 'deliverables' => ['Registre des risques', 'Matrice de criticité', 'Mapping taxonomie DGCPT'], 'question_topics' => ['risques', 'traitement'], 'risk_category' => 'Maîtrise des risques', 'criticality' => 'critical'],
            ['code' => 'SUIVI', 'name' => 'Restitution et suivi', 'objective' => 'Valider le rapport, suivre les recommandations et alimenter le dashboard consolidé.', 'expected_outcome' => 'Plan d’actions pilotable.', 'deliverables' => ['Rapport final', 'Plan d’actions', 'Tableau de suivi'], 'question_topics' => ['reporting', 'suivi'], 'risk_category' => 'Reporting', 'criticality' => 'high'],
        ];
    }

    /**
     * @param  array<int, string>  $taxonomyCodes
     * @return array<int, array<string, string>>
     */
    private function questionsFor(string $frameworkKey, array $taxonomyCodes): array
    {
        $questions = [
            ['topic' => 'gouvernance', 'question' => 'Les rôles, responsabilités et pouvoirs de décision sont-ils formalisés et connus ?'],
            ['topic' => 'périmètre', 'question' => 'Le périmètre de mission couvre-t-il les processus, applications, données et acteurs critiques ?'],
            ['topic' => 'contrôles', 'question' => 'Les contrôles clés existent-ils, sont-ils documentés et régulièrement exécutés ?'],
            ['topic' => 'conformité', 'question' => 'Les exigences du référentiel sont-elles traduites en critères vérifiables pour l’entité ?'],
            ['topic' => 'données', 'question' => 'Les données sensibles sont-elles inventoriées, protégées et traçables ?'],
            ['topic' => 'historique', 'question' => 'Les anciennes cartographies et plans d’actions ont-ils été revus avant les travaux ?'],
            ['topic' => 'opérations', 'question' => 'Les activités opérationnelles respectent-elles les procédures approuvées ?'],
            ['topic' => 'preuves', 'question' => 'Les preuves collectées sont-elles suffisantes, fiables, datées et rattachées aux constats ?'],
            ['topic' => 'risques', 'question' => 'Les risques identifiés sont-ils évalués selon impact, probabilité, criticité et exposition résiduelle ?'],
            ['topic' => 'traitement', 'question' => 'Chaque risque significatif dispose-t-il d’un propriétaire, d’un plan de traitement et d’une échéance ?'],
            ['topic' => 'reporting', 'question' => 'Le rapport distingue-t-il constats, causes, conséquences, risques et recommandations ?'],
            ['topic' => 'suivi', 'question' => 'Les recommandations font-elles l’objet d’un suivi périodique et d’une preuve de mise en œuvre ?'],
        ];

        return collect($questions)->map(fn (array $question) => [
            'framework' => $frameworkKey,
            'topic' => $question['topic'],
            'question' => $question['question'],
            'taxonomy_hint' => implode(',', $taxonomyCodes),
        ])->all();
    }

    private function taxonomyLabel(string $code): string
    {
        return [
            'GOV' => 'Gouvernance, stratégie et conformité',
            'FIN' => 'Opérations financières et comptables',
            'SI' => 'Systèmes d’information et cybersécurité',
            'OPS' => 'Processus, performance et qualité de service',
            'CTRL' => 'Contrôle interne et maîtrise des risques',
            'DATA' => 'Données, traçabilité et reporting',
        ][$code] ?? $code;
    }
}
