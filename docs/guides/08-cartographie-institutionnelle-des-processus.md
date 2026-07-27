# Cartographie institutionnelle des processus

## Finalité

Ce module documente les processus de la DGCPT indépendamment des missions d’audit. Le Pôle informatique peut administrer le catalogue initial des processus IT, mais le modèle est transversal et peut être attribué à toute administration, direction, département, pôle ou division.

## Habilitation par structure

Le super administrateur ouvre **Pilotage des processus > Habilitations processus** puis attribue à une structure :

- un état actif ou désactivé ;
- un rôle par défaut ;
- une transmission facultative de l’accès aux structures descendantes.

L’habilitation la plus proche de la structure de l’utilisateur est prioritaire. Un droit propre à une sous-structure peut donc remplacer le droit hérité de sa direction parente.

### Rôles

- **Consultation** : consulter les processus visibles et télécharger leurs documents ;
- **Contribution** : créer et documenter les processus de sa structure ;
- **Validation** : contribuer, publier et archiver ;
- **Administration** : gérer également les domaines de processus ;
- **Super administrateur** : accès institutionnel complet et gestion des habilitations.

## Modèle transversal

Chaque processus comprend une structure propriétaire et zéro ou plusieurs structures participantes. Sa visibilité est choisie à la création :

- **Structure propriétaire** : consultation limitée à la structure propriétaire ;
- **Structures participantes** : partage entre le propriétaire et les participants ;
- **Toute la DGCPT** : visibilité institutionnelle après publication.

Un contributeur ne peut déclarer comme propriétaire que sa propre structure. Le super administrateur peut créer un processus pour n’importe quelle structure.

## Contenu fonctionnel disponible

- domaines de processus ;
- code, nom, objectif et description ;
- responsable fonctionnel ;
- criticité, priorité et niveau de maturité ;
- activités ordonnées avec durée, responsable et documents produits ;
- entrées, sorties, acteurs, applications et actifs ;
- indicateurs avec unité, cible, valeur et méthode de calcul ;
- documents PDF, Word, Excel, images ou vidéos ;
- structures participantes ;
- vue graphique horizontale des activités ;
- recherche et indicateurs de synthèse ;
- historique des événements et versions.

## Workflow

Le cycle de vie est :

1. Brouillon ;
2. Soumission à validation ;
3. Publication ;
4. Révision, avec incrément de version ;
5. Archivage.

La publication et l’archivage exigent au minimum le rôle **Validation**. Chaque événement est horodaté avec l’utilisateur et la version concernés.

## Stockage et sécurité

Les documents sont enregistrés sur le disque privé Laravel dans `storage/app/private/process-documents`. Leur téléchargement passe par une route authentifiée qui vérifie l’accès au module et la visibilité du processus.

La migration associée est `2026_07_27_140000_create_institutional_process_mapping_module.php`. Elle crée uniquement de nouvelles tables et ne modifie pas les données d’audit existantes.

## Limites de cette première version

Les liaisons many-to-many avec les risques, contrôles, questionnaires, recommandations, missions, constats et plans d’action seront ajoutées dans la phase d’intégration audit. Les exports PDF/Word/Excel/PNG/SVG, BPMN 2.0, API REST, diagramme glisser-déposer et suggestions IA constituent également une phase ultérieure.

## Contrôles après déploiement

1. Vérifier la migration avec `php artisan migrate:status`.
2. Se connecter comme super administrateur et ouvrir les habilitations processus.
3. Activer le module pour le Pôle informatique avec le rôle Administration.
4. Activer ou hériter un rôle Consultation pour une seconde structure.
5. Créer un domaine puis un processus avec cette seconde structure comme participante.
6. Ajouter des activités, entrées, sorties, acteurs, KPI et documents.
7. Soumettre puis publier le processus avec un utilisateur disposant du rôle Validation.
8. Vérifier qu’un utilisateur sans habilitation reçoit une réponse HTTP 403.