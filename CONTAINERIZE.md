# CONTAINERIZE - Solo/masch-backend

Archetype: laravel. Generated 2026-11-20T22:42:22+07:00 by .opencode/scripts/render.sh on branch chore/containerize (uncommitted).

## Generated files
- Dockerfile (template: Dockerfile.php-fpm)
- Jenkinsfile (CI only; parameters REGISTRY, HARBOR_PROJECT, HARBOR_CRED_ID)
- .dockerignore
- .containerize/nginx-fpm.conf.template
- .containerize/supervisord.conf
- .containerize/php-fpm-pool.conf
- .containerize/entrypoint-fpm.sh

## Local run
```sh
bash .opencode/scripts/build.sh Solo/masch-backend
bash .opencode/scripts/smoke.sh Solo/masch-backend
```
Manual run: podman run --rm --network=host --env-file /home/richie/Documents/Programming/Projects/.smoke.env -e PORT=<free-port> containerize/masch-backend:local

## Required env var NAMES (values never stored here)
APP_DEBUG APP_ENV APP_KEY APP_NAME APP_URL AWS_ACCESS_KEY_ID AWS_BUCKET AWS_DEFAULT_REGION AWS_SECRET_ACCESS_KEY BROADCAST_DRIVER CACHE_DRIVER DB_CONNECTION DB_DATABASE DB_HOST DB_PASSWORD DB_PORT DB_USERNAME LOG_CHANNEL MAIL_ENCRYPTION MAIL_FROM_ADDRESS MAIL_FROM_NAME MAIL_HOST MAIL_MAILER MAIL_PASSWORD MAIL_PORT MAIL_USERNAME MIX_PUSHER_APP_CLUSTER MIX_PUSHER_APP_KEY PUSHER_APP_CLUSTER PUSHER_APP_ID PUSHER_APP_KEY PUSHER_APP_SECRET QUEUE_CONNECTION REDIS_HOST REDIS_PASSWORD REDIS_PORT SESSION_DRIVER SESSION_LIFETIME 

## DB smoke
boot + healthcheck only (DB not smoke-tested). db_driver: mysql. Never migrate/seed/modify; DBs are live.

## Flags
- PHP 7.2 has no published FrankenPHP image; using php-fpm-alpine (EOL base) - HUMAN REVIEW
- verify agent (composer:2, PHP 8.4) newer than repo PHP 7.2: composer install uses --ignore-platform-req=php (lint only)
- agent label 'php' unverified on k3s Jenkins - confirm with user

Smoke results: 2026-11-20T23:11:03+07:00 boot=PASS http=200 db=skipped (artisan db:show not available on this framework version; no read-only DB check)
Smoke results: 2026-11-21T21:30:37+07:00 boot=PASS http=200 db=skipped (legacy mysqli app; pgsql not supported without a code rewrite)
