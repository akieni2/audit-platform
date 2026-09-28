<?php

return [
    'user_agent_marker' => 'DGCPT-Android/',

    'always_allowed_routes' => [
        'dashboard',
        'profile.*',
        'notifications.*',
        'search',
        'logout',
    ],

    'menus' => [
        'missions' => [
            'label' => 'Missions et services audités',
            'description' => 'Fiches de mission, équipes, services, preuves et constats.',
            'routes' => ['missions.*', 'services.*', 'mission-services.*', 'mission-documents.*', 'document-requests.*', 'constats.*', 'audit-recommendations.*'],
        ],
        'questionnaires' => [
            'label' => 'Questionnaires',
            'description' => 'Bibliothèque, assistant, affectations et réponses.',
            'routes' => ['questionnaire-*', 'mission-questionnaires.*', 'entretiens.*', 'module.questionnaires', 'module.entretiens', 'dgcpt.questionnaire-import.*'],
        ],
        'risks' => [
            'label' => 'Risques et cartographie',
            'description' => 'Cartographie, SWOT, RACI, contrôles et actions correctives.',
            'routes' => ['cartographie.*', 'swot*', 'raci*', 'risques.*', 'risks.*', 'actions.*', 'controles.*', 'module.risques', 'module.actions'],
        ],
        'workflows' => [
            'label' => 'Workflows',
            'description' => 'Conception et exécution des circuits de validation.',
            'routes' => ['workflow-*'],
        ],
        'cfdt' => [
            'label' => 'CFDT — Formation',
            'description' => 'Cours, supports, QCM, évaluations et certificats.',
            'routes' => ['formation.*', 'cfdt.*', 'certificats.*'],
        ],
        'correspondence' => [
            'label' => 'Gestion du courrier',
            'description' => 'Courriers entrants, sortants, transmissions et suivi.',
            'routes' => ['correspondence.*'],
        ],
        'administrative_work' => [
            'label' => 'Travail administratif',
            'description' => 'Tâches administratives transversales.',
            'routes' => ['administrative-work.*'],
        ],
        'processes' => [
            'label' => 'Cartographie des processus',
            'description' => 'Domaines et processus institutionnels.',
            'routes' => ['process-mapping.*', 'institutional-processes.*', 'processus.*', 'module.processus'],
        ],
        'assets' => [
            'label' => 'Actifs informatiques',
            'description' => 'Inventaire et gestion des actifs institutionnels.',
            'routes' => ['it-assets.*', 'institutional-assets.*', 'actifs.*', 'module.actifs'],
        ],
        'ai' => [
            'label' => 'Copilote IA',
            'description' => 'Assistance et recommandations IA.',
            'routes' => ['ai.*'],
        ],
        'reports' => [
            'label' => 'Rapports et consolidation',
            'description' => 'Rapports, consolidation et tableaux de synthèse.',
            'routes' => ['module.rapports', 'enterprise.*', 'executive.*', 'dashboard.executive', 'dgcpt.*'],
        ],
        'forms' => [
            'label' => 'Formulaires',
            'description' => 'Conception et utilisation des formulaires dynamiques.',
            'routes' => ['form-builder.*'],
        ],
        'administration' => [
            'label' => 'Administration',
            'description' => 'Utilisateurs, structures, sécurité et habilitations mobiles.',
            'routes' => ['admin.*'],
        ],
    ],
];
