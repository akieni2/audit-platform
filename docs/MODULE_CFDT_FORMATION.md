# Module CFDT — Formation et documentation du Trésor

## Finalité

Le module CFDT fournit à la DGCPT un espace interne de formation continue. Il permet de publier des supports pédagogiques, d'évaluer les agents par QCM et de délivrer des certificats vérifiables.

## Rôles et habilitations

- **Apprenant** : consulte les formations auxquelles il est affecté, passe les évaluations et télécharge ses certificats.
- **Formateur** : crée les formations et les questions, puis affecte les agents.
- **Validateur** : contrôle et publie les formations, en plus des fonctions du formateur.
- **Administrateur CFDT** : administre le contenu et les affectations.
- **Super administrateur** : dispose de tous les droits et attribue ou retire individuellement les rôles CFDT.

L'habilitation est indépendante du rôle métier de l'agent et peut être révoquée à tout moment depuis **Formation professionnelle → Espace CFDT → Gérer les habilitations**.

## Parcours fonctionnel

1. Le formateur crée une formation avec son code, ses objectifs, son support, sa durée, son seuil de réussite et son nombre maximal de tentatives.
2. Il compose un QCM à choix unique ou multiple et indique les réponses correctes, les explications et les points.
3. Le validateur ou l'administrateur publie la formation.
4. Le formateur affecte un ou plusieurs agents habilités.
5. L'apprenant passe le QCM. Le système calcule automatiquement son score.
6. En cas de réussite, un certificat PDF nominatif est créé avec un numéro unique et une adresse publique de vérification d'intégrité.

Les valeurs initiales recommandées sont un seuil de réussite de **70 %** et un maximum de **3 tentatives**. Elles restent configurables pour chaque formation.

## Données et sécurité

Les formations, affectations, tentatives et certificats disposent de tables dédiées. La suppression d'une formation entraîne la suppression de ses affectations, tentatives et certificats. Un apprenant ne peut télécharger que son propre certificat ; les formateurs, validateurs, administrateurs CFDT et super administrateurs peuvent assurer le contrôle pédagogique.

## Déploiement et retour arrière

Le déploiement requiert une sauvegarde MySQL préalable, puis `php artisan migrate --force`. Le retour arrière applicatif doit être réalisé avec le commit Git de sauvegarde ; la migration peut être annulée avec `php artisan migrate:rollback --step=1` uniquement si aucune donnée CFDT ne doit être conservée.

## Évolutions prévues

- banque de questions réutilisable et import de supports ;
- chronométrage serveur et randomisation avancée ;
- tableau de bord de progression par direction et structure ;
- convocations et notifications ;
- assistance IA locale pour suggérer des QCM, soumise à validation humaine.
