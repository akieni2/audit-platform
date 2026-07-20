# Organisation institutionnelle DGCPT

## Principe

L’organigramme est construit avec des structures imbriquées. Chaque structure possède un type, une structure parente facultative selon son niveau, une fonction dirigeante et un titulaire utilisateur.

## Inspection des Services

```text
Inspection des Services — Inspecteur des Services
├── PI — Inspecteur des Services adjoint
│   ├── Inspecteurs vérificateurs
│   └── Inspecteurs vérificateurs adjoints
├── PMAR — Inspecteur des Services adjoint
└── PCPC — Inspecteur des Services adjoint
```

L’Inspecteur des Services adjoint affecté comme superviseur de son pôle peut créer les missions du pôle et affecter les membres éligibles de l’équipe. Les rôles missionnels restent « Chef de mission », « Inspecteur vérificateur » et « Inspecteur vérificateur adjoint ».

## Direction des Systèmes d’Information

```text
Direction des Systèmes d’Information — Directeur
├── Direction adjointe — Directeur adjoint
├── Service — Chef de service
│   └── Agents opérationnels
└── Service — Chef de service
```

## Niveaux disponibles

- Direction générale ;
- Administration ;
- Direction ;
- Département (niveau historique conservé pour compatibilité) ;
- Inspection des Services ;
- Sous-direction ;
- Pôle ;
- Service ;
- Cellule ;
- Cabinet.

Les pôles et sous-directions doivent être rattachés à une direction, une administration ou l’Inspection des Services. Les services doivent être rattachés à une structure dirigeante ou intermédiaire. Les cycles hiérarchiques sont interdits.

## Fonctions institutionnelles complémentaires

Le catalogue de rôles inclut désormais Directeur, Directeur adjoint, Chef de service et Agent opérationnel, en complément des fonctions propres à l’Inspection des Services.

Les fiches de poste sont préparées dans le profil organisationnel de chaque structure : appellation, description et activités principales. Elles pourront ensuite être enrichies sans modifier la hiérarchie de base.

## Espace d’audit de la structure

La création d’une administration, direction, inspection, département, sous-direction ou d’un pôle impose le choix d’un référentiel d’audit actif. Le système provisionne alors un espace isolé comprenant :

- un workflow d’audit personnalisable ;
- une bibliothèque de questions et de questionnaires ;
- une bibliothèque de contrôles ;
- le périmètre de cartographie des risques de la structure ;
- un modèle RACI ;
- un modèle SWOT ;
- les journaux et règles d’isolation propres à la structure.

Le provisionnement est idempotent. Une modification ultérieure du référentiel réaligne les liens de gouvernance sans supprimer les contenus personnalisés de la structure.
