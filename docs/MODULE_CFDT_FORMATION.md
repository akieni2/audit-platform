# Module CFDT — Formation et documentation du Trésor

## Finalité

Le module CFDT fournit à la DGCPT un espace interne de formation continue. Il permet de publier des supports pédagogiques, d'évaluer les agents par QCM et de délivrer des certificats vérifiables.

## Rôles et habilitations

- **Apprenant** : consulte les formations auxquelles il est affecté, passe les évaluations et télécharge ses certificats.
- **Formateur** : crée les formations et les questions, puis affecte les agents.
- **Validateur** : contrôle et publie les formations, en plus des fonctions du formateur.
- **Administrateur CFDT** : crée les comptes apprenants, formateurs et validateurs, administre le contenu et les affectations. Il ne peut ni créer ni modifier un administrateur CFDT.
- **Super administrateur** : dispose de tous les droits et demeure le seul à pouvoir nommer ou retirer un administrateur CFDT.

L'habilitation est indépendante du rôle métier de l'agent et peut être révoquée à tout moment depuis **Formation professionnelle → Espace CFDT → Gérer les habilitations**.

## Parcours fonctionnel

1. Le formateur crée une formation avec son code, ses objectifs, son support, sa durée, son seuil de réussite et son nombre maximal de tentatives.
2. Il compose un QCM à choix unique ou multiple et indique les réponses correctes, les explications et les points.
3. Le validateur ou l'administrateur publie la formation.
4. Le formateur affecte le test à des agents nommément désignés, à une ou plusieurs structures de l'organigramme (avec leurs sous-structures), ou à des catégories professionnelles telles que directeur, chef de service ou inspecteur vérificateur.
5. Une date limite obligatoire est enregistrée. Chaque agent reçoit une notification interne et une invitation par courriel contenant un lien personnel ; le test et le lien sont bloqués après l'échéance.
6. L'apprenant retrouve sur son tableau de bord ses tests, échéances, tentatives, meilleur score, moyenne et certificats.
7. L'apprenant passe le QCM. Le système calcule automatiquement son score.
8. En cas de réussite, un certificat PDF nominatif est créé avec un numéro unique et une adresse publique de vérification d'intégrité.

Les valeurs initiales recommandées sont un seuil de réussite de **70 %** et un maximum de **3 tentatives**. Elles restent configurables pour chaque formation.

## Données et sécurité

Les formations, affectations, tentatives et certificats disposent de tables dédiées. Chaque affectation conserve sa source, ses critères, son échéance et un jeton personnel unique. La suppression d'une formation entraîne la suppression de ses affectations, tentatives et certificats. Un apprenant ne peut télécharger que son propre certificat ; les formateurs, validateurs, administrateurs CFDT et super administrateurs peuvent assurer le contrôle pédagogique.

L'envoi réel des courriels exige un transport SMTP valide dans le fichier `.env`. Une panne du fournisseur de messagerie n'annule jamais l'affectation : la notification interne demeure disponible et l'échec SMTP est journalisé.

## Déploiement et retour arrière

Le déploiement requiert une sauvegarde MySQL préalable, puis `php artisan migrate --force`. Le retour arrière applicatif doit être réalisé avec le commit Git de sauvegarde ; la migration peut être annulée avec `php artisan migrate:rollback --step=1` uniquement si aucune donnée CFDT ne doit être conservée.

## Évolutions prévues

- banque de questions réutilisable et import de supports ;
- chronométrage serveur et randomisation avancée ;
- tableau de bord consolidé de progression par direction et structure ;
- assistance IA locale pour suggérer des QCM, soumise à validation humaine.
