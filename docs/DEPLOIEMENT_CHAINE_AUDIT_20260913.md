# Déploiement — chaîne probante d'audit

## Périmètre

Cette livraison relie les questionnaires, réponses, preuves, constats, risques, recommandations, actions correctives, validations, rapport final et indicateurs COPRI/DG.

## Sauvegardes de référence

Avant la première migration, les sauvegardes suivantes ont été créées sur le VPS :

- `/root/backups/audit-platform/audit_platform_20260913_chain_audit.sql.gz`
- `/root/backups/audit-platform/audit_platform_files_20260913_chain_audit.tar.gz`

Elles doivent rester accessibles uniquement à `root` (`chmod 600`).

## Contrôles avant déploiement

```bash
cd /var/www/audit-platform
git status
git rev-parse --short HEAD
php artisan migrate:status
supervisorctl status
```

Le répertoire Git doit être propre. Une nouvelle sauvegarde MySQL horodatée doit être créée avant chaque nouvelle tentative de migration.

## Déploiement

```bash
cd /var/www/audit-platform
php artisan down --retry=60
git fetch origin main
git reset --hard origin/main
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
supervisorctl restart audit-horizon
supervisorctl restart audit-platform-worker:*
supervisorctl restart audit-reverb
php artisan up
```

## Contrôles après déploiement

```bash
php artisan migrate:status
php artisan route:list --name=constats
supervisorctl status
curl -fsS http://127.0.0.1:8000/up
git status
```

Dans l'interface, vérifier avec une mission de test : création d'un constat, liaison d'une preuve, revue d'équipe, validation du chef de mission, réponse contradictoire, finalisation, conversion en risque, validation de la recommandation, création et clôture d'une action, puis génération du PDF.

## Retour arrière applicatif

Identifier le commit précédant la livraison, puis :

```bash
cd /var/www/audit-platform
php artisan down --retry=60
git reset --hard <COMMIT_PRECEDENT>
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

## Restauration complète de la base

Cette opération détruit l'état de la base postérieur à la sauvegarde. Elle ne doit être utilisée qu'après validation explicite et après création d'une sauvegarde de l'état en échec.

```bash
gunzip -c /root/backups/audit-platform/audit_platform_20260913_chain_audit.sql.gz | mysql -u root audit_platform
tar -xzf /root/backups/audit-platform/audit_platform_files_20260913_chain_audit.tar.gz -C /var/www/audit-platform
chown www-data:www-data /var/www/audit-platform/.env
chmod 640 /var/www/audit-platform/.env
chown -R www-data:www-data /var/www/audit-platform/storage
```

Après restauration, reconstruire les caches et redémarrer les workers.
