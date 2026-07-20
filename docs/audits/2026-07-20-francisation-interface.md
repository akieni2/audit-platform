# Francisation de l’interface — 20 juillet 2026

## Objectif

Uniformiser les libellés visibles en français tout en conservant `workflow` comme terme fonctionnel autorisé et les codes anglais comme valeurs techniques internes.

## Vocabulaire appliqué

| Valeur ou libellé technique | Affichage français |
|---|---|
| `low` | Faible |
| `medium` | Moyen |
| `high` | Élevé |
| `critical` | Critique |
| dashboard | Tableau de bord |
| runtime | Exécution |
| builder | Concepteur |
| mappings | Correspondances |
| review board | Comité de revue |
| top manager | Responsable hiérarchique |
| accountable | Responsable final |
| risk maturity | Maturité des risques |
| recommendations | Recommandations |
| status | Statut |
| AI Copilot | Copilote IA |

## Choix d’architecture

Les codes persistés en base et utilisés par les API ne sont pas modifiés. La classe `App\Support\UiLabel` traduit les valeurs au moment de l’affichage. Ce choix préserve la compatibilité avec les données, filtres, workflows et intégrations existants.

Le mot `workflow` reste volontairement présent conformément à la décision métier.

## Périmètre corrigé

- référentiels, méthodologies et criticités ;
- navigation principale et navigation historique ;
- tableaux de bord exécutifs ;
- registre des risques et comité de revue ;
- pages d’exécution et d’observabilité des workflows ;
- concepteurs de workflows, questionnaires, formulaires, SWOT et RACI ;
- affectations RACI ;
- recommandations SWOT ;
- organigramme et responsables hiérarchiques ;
- rapports de mission et statuts dynamiques.

## Contrôle de non-régression

Le test `UiLabelTest` vérifie les traductions des niveaux de criticité, du rôle RACI et des statuts courants. Les recherches statiques doivent ignorer les noms de routes, variables, clés JSON et valeurs de formulaire, qui restent techniques et ne sont pas affichés.
