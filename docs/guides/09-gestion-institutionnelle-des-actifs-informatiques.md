# Gestion institutionnelle des actifs informatiques

## Finalité

Le module constitue l’inventaire transversal des actifs informatiques de la DGCPT conformément aux principes ISO 27001. Il est distinct de l’ancien écran d’actifs rattaché aux missions d’audit.

## Habilitations

Le super administrateur accorde le module à une structure avec un rôle Consultation, Contribution, Validation ou Administration. Le droit peut être transmis aux sous-structures et révoqué localement. Une structure possède l’actif et peut le partager avec des structures participantes ou avec toute la DGCPT.

## Fiche et criticité

La fiche conserve l’identifiant, la catégorie, le propriétaire, le responsable, la localisation, la mise en service, le fabricant, le modèle, le numéro de série, l’état et la valeur. La criticité repose sur des notes de 1 à 5 pour disponibilité, confidentialité, intégrité, traçabilité et probabilité. Le score automatique `impact maximal × probabilité` produit les niveaux Faible, Modérée, Importante ou Critique.

L’analyse d’impact comprend les services, applications et utilisateurs touchés, la solution de secours, le RTO, le RPO, la sauvegarde, la redondance, l’obsolescence et les points uniques de défaillance.

## Relations

Un actif peut dépendre d’autres actifs et être associé aux processus institutionnels. Les contrôles et documents sont conservés sur sa fiche. La chaîne de dépendances est visualisée horizontalement et chaque nœud ouvre la fiche correspondante.

## Workflow et sécurité

Un contributeur crée un brouillon. Le passage en service et l’archivage nécessitent le rôle Validation. Les documents sont privés dans `storage/app/private/asset-documents` et téléchargés par une route authentifiée. Toutes les opérations structurantes alimentent l’historique.

## Migration et déploiement

La migration `2026_07_27_170000_create_institutional_it_asset_management_module.php` ajoute uniquement de nouvelles tables. Après migration, le super administrateur ouvre **Patrimoine informatique > Habilitations actifs**, active le Pôle informatique avec le rôle Administration puis crée les catégories initiales : Ressources humaines, Infrastructure, Serveurs, Applications, Bases de données, Données, Services et Locaux.

## Évolutions prévues

Les risques, recommandations, questionnaires, missions et incidents seront reliés dans une phase ultérieure. Sont également prévus : découverte SNMP, Active Directory, VMware/Hyper-V/Proxmox, GLPI/Intune, API REST, exports, heatmap avancée et suggestions IA.