# Gestion du courrier et traitement administratif

## Séparation fonctionnelle

La plateforme contient désormais deux domaines administratifs indépendants des fonctions d'audit :

1. **Gestion électronique du courrier (GEC)** : registre officiel des courriers entrants et sortants, documents PDF, références, délais, affectations et historique de circulation.
2. **Traitement administratif** : instructions et tâches déclenchées par un courrier ou créées indépendamment, avec responsable, agent, priorité, échéance et état de validation.

Cette séparation évite de confondre le document institutionnel avec le travail administratif qu'il déclenche.

## Autorisations

Le super administrateur attribue séparément à chaque utilisateur :

- l'accès au menu **Gestion du courrier** ;
- l'accès au menu **Traitement administratif**.

Les deux décisions sont indépendantes. Le super administrateur conserve toujours l'accès aux deux modules. Un utilisateur autorisé ne voit que les données de son périmètre hiérarchique, celles qui lui sont affectées ou celles qu'il a créées.

## Noyau GEC disponible

- courrier entrant ou sortant ;
- référence automatique `DGCPT-CR-AAAA-NNNNNN` ;
- expéditeur, destinataire, objet, type et description ;
- confidentialité et urgence ;
- calcul automatique d'une échéance SLA ;
- archivage du document PDF ;
- jeton sécurisé destiné au futur QR Code ;
- affectation à une structure et à un agent ;
- journal chronologique des mouvements ;
- recherche par référence, objet ou expéditeur ;
- indicateurs : reçus du jour, à traiter, urgents et retards ;
- création d'une instruction administrative depuis un courrier.

## Noyau de traitement administratif disponible

- tâche liée ou non à un courrier ;
- titre, description et résultat attendu ;
- structure, agent chargé et responsable de validation ;
- priorité et échéance ;
- états : brouillon, affectée, en cours, soumise, validée et clôturée ;
- tableaux de bord des tâches actives, personnelles, en retard et validées ;
- visibilité limitée au périmètre de l'utilisateur.

## Extensions planifiées

Les extensions suivantes nécessitent des travaux et infrastructures complémentaires :

- génération graphique et impression des QR Codes ;
- scan mobile pour dépôt, retrait et ramassage ;
- notifications par courriel et SMS ;
- géolocalisation des livraisons ;
- signature manuscrite sur tablette ;
- signature électronique qualifiée ;
- annotations graphiques sur PDF ;
- pièces jointes et commentaires versionnés sur les tâches ;
- mesure détaillée du temps passé à chaque étape ;
- classements de performance et carte des flux.

Ces extensions pourront être ajoutées sans modifier les frontières entre la GEC et le traitement administratif.
## Mise en service

La migration `2026_07_27_090000_create_correspondence_and_administrative_work_modules.php` :

- ajoute aux utilisateurs les deux autorisations individuelles ;
- crée les registres des courriers, des mouvements et des tâches administratives ;
- ne modifie ni les missions d’audit ni leurs données.

Après le déploiement et l’exécution des migrations, le super administrateur ouvre la fiche d’un utilisateur puis active séparément **Gestion du courrier** et **Traitement administratif**. Les nouveaux menus apparaissent à la reconnexion de l’utilisateur.

Le stockage des PDF utilise le disque privé local de Laravel. Le répertoire `storage` doit rester accessible en écriture à l’utilisateur du serveur web.

## Contrôles après déploiement

1. Vérifier que la migration apparaît comme exécutée avec `php artisan migrate:status`.
2. Ouvrir une fiche utilisateur et activer les deux modules.
3. Se reconnecter avec cet utilisateur et vérifier l’apparition des deux menus.
4. Enregistrer un courrier de test avec un PDF.
5. Affecter le courrier et contrôler son historique.
6. Créer une instruction administrative depuis le courrier.
7. Vérifier qu’un utilisateur non autorisé ne voit pas les menus et reçoit une interdiction HTTP 403 s’il tente un accès direct.

## Sauvegarde et retour arrière

Une sauvegarde MySQL doit être réalisée avant la migration. En cas d’incident, privilégier la restauration de cette sauvegarde et le retour au commit Git précédent. La commande `php artisan migrate:rollback --step=1` supprime les tables des deux modules ainsi que les deux colonnes d’autorisation ; elle ne doit être utilisée qu’après avoir sauvegardé les courriers et tâches éventuellement saisis depuis la mise en service.
