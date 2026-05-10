# Exploitation production — DGCPT / COPRI

## Prérequis serveur

- PHP 8.2+, extensions `pdo`, `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `redis` (optionnel mais recommandé).
- Redis (queues, cache, Reverb scaling optionnel).
- Supervisor (`queue:work` ou Horizon, Reverb).
- Nginx + PHP-FPM (ou équivalent).
- Certificats TLS.

## Installation applicative

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm ci && npm run build
```

Packages ajoutés au projet : **Horizon**, **Pulse**, **Reverb**, **Predis**. Après `composer install` :

```bash
php artisan horizon:install
php artisan pulse:install
php artisan reverb:install
```

Publier les configs si besoin : `vendor:publish` pour Horizon/Pulse selon la doc Laravel.

## Variables d’environnement critiques

| Variable | Rôle |
|----------|------|
| `APP_ENV=production` | Mode prod |
| `APP_DEBUG=false` | Pas de fuite d’erreurs |
| `CACHE_STORE=redis` | Cache distribué |
| `QUEUE_CONNECTION=redis` | Files d’attente |
| `BROADCAST_CONNECTION=reverb` | Temps réel |
| `QUEUE_MISSION_PDF=true` | PDF asynchrone (`GenerateMissionPdfJob`) |
| `EXEC_KPI_CACHE_TTL` | TTL cache KPI nationaux (secondes) |
| `REVERB_*` / `VITE_REVERB_*` | Serveur Reverb + client Echo |

Configurer **Pulse** et **Horizon** pour restreindre l’accès aux profils d’administration (voir `AppServiceProvider` pour Horizon).

## Processus superviseurs

- **Workers** : voir `deploy/supervisor/laravel-worker.example.conf` ou utiliser **Horizon** (`php artisan horizon`) avec la config publiée.
- **Reverb** : `deploy/supervisor/reverb.example.conf` ou process manager équivalent derrière TLS/proxy.

## Santé & supervision

- Sonde readiness : `GET /health/ready` (JSON, HTTP 503 si base indisponible).
- Sonde Laravel : `GET /up` (bootstrap).

## Planificateur (cron)

Une entrée cron utilisateur applicatif :

```
* * * * * cd /var/www/audit-platform && php artisan schedule:run >> /dev/null 2>&1
```

Les tâches sont définies dans `bootstrap/app.php` (`withSchedule`).

## Sauvegardes

Script exemple SQLite : `scripts/backup-sqlite.example.sh`. En production réelle : snapshots bases managées, sauvegardes fichiers `storage/app`, politique de rétention et tests de restauration.

## Observabilité externe

Brancher **Sentry**, métriques Prometheus, ou agrégation des logs (`storage/logs`) selon la doctrine SI du ministère. Telescope reste réservé au **local** si installé (`require-dev`).
