# masch-backend — developer notes

masch-backend is the JSON API for a cinema screening-schedule system. It is consumed by
the paired `Solo/masch-frontend` Vue SPA (product name CinemaXIX), which calls the
`/api/v1/*` endpoints. The API has two surfaces: open browsing and auth endpoints, and
catalogue CRUD guarded by a Passport OAuth token. All domain UI lives in that frontend;
this repo serves no screens beyond a stock Laravel welcome page.

## Stack

Versions are taken from lockfiles, which are the truth; the manifests state minimums.

| Item | Version | Evidence |
|---|---|---|
| Framework | Laravel v7.28.1 | composer.lock:1004-1005; manifest asks `^7.24` (composer.json:15) |
| Auth | laravel/passport v9.3.2 | composer.lock:1162-1163; exact pin in composer.json:16 |
| Locked packages | fideloper/proxy 4.4.0, fruitcake/laravel-cors v2.0.2, guzzlehttp/guzzle 6.5.5, laravel/tinker v2.4.2 | composer.lock package entries |
| Language | PHP 7.2.x runtime | php:7.2-fpm-alpine (Dockerfile:2,15); composer minimum `^7.2.5` (composer.json:11) |
| Front-end (client) | Vue 2.6.12 exact | package-lock.json |
| Front-end build | laravel-mix `^5.0.1`, sass `^1.15.2` (manifest ranges only) | package.json:15,20,22 |
| Package managers | composer 2 (multi-stage binary copy) + npm | Dockerfile:1,4; package-lock.json |

The lockfile carries no exact PHP patch; the effective runtime is whatever the
`php:7.2-fpm-alpine` image ships. PHP 7.2 is past end of life and is flagged in
`CONTAINERIZE.md`. The manifest vs lockfile disagreement is the normal one: `^7.24`
resolves to v7.28.1, so trust the lock.

Build commands: `composer install --no-dev --no-scripts --no-autoloader` then
`composer dump-autoload --optimize --no-dev` (Dockerfile:10,12). `npm run production`
exists (`cross-env NODE_ENV=production node_modules/webpack/bin/webpack.js ...`,
package.json:9-10) but the Dockerfile never runs npm — there is no built-asset stage,
and `webpack.mix.js` output into `public/` is irrelevant to an API.

## Architecture

Request flow: the entry point is `public/index.php` (standard Laravel 7). nginx serves
document root `/app/public` (`ENV DOC_ROOT=/app/public`, Dockerfile:31; `root ${DOC_ROOT}`
in `.containerize/nginx-fpm.conf.template:26`) and falls back to
`/index.php?$query_string` (`nginx-fpm.conf.template:29-31`). nginx passes PHP to
`php-fpm` over `unix:/tmp/php-fpm.sock` (`nginx-fpm.conf.template:33-38`).
`supervisord -c /etc/supervisord.conf` (Dockerfile:35) launches nginx
(`nginx -c /tmp/nginx/nginx.conf -g "daemon off;"`) and `php-fpm -F`
(`.containerize/supervisord.conf:10-18`). The port is 8080 (`ENV PORT=8080`, `EXPOSE 8080`
— Dockerfile:31,33; `listen ${PORT}` in the template). The entrypoint renders the nginx
config from a template with `envsubst '${PORT} ${DOC_ROOT}'`
(`.containerize/entrypoint-fpm.sh:1-5`).

Routing: the `api` prefix is applied in `app/Providers/RouteServiceProvider.php:65-68`;
the `api` middleware group is `throttle:60,1` + `SubstituteBindings`
(`app/Http/Kernel.php:44-47`); the `web` group is at `RouteServiceProvider.php:52-56`.
There is no global auth gate — the global stack is only TrustProxies, CORS, maintenance
mode, ValidatePostSize, TrimStrings, ConvertEmptyStringsToNull
(`app/Http/Kernel.php:20-28`). Rendering is JSON only: the single Blade view is the stock
`welcome.blade.php`, and no Vue components are registered (`resources/js/app.js:1-20`,
with the Echo/Pusher block commented out). Background work: the queue connection is
`sync`, no jobs are dispatched anywhere, and the scheduler is empty
(`app/Console/Kernel.php:25-27`).

## Data model

| Entity | Migration | Key columns | Relationships |
|---|---|---|---|
| users | `2014_10_12_000000_create_users_table.php:16-26` | `id`, `name`, `enum role [admin,user]`, `username`, `password`, `remember_token` | Referenced by Passport tokens; model `app/User.php:9-30` uses `HasApiTokens` |
| password_resets | `2014_10_12_100000_create_password_resets_table.php:16-21` | `email` (indexed), `token`, `created_at` | No route uses it |
| failed_jobs | `2019_08_19_000000_create_failed_jobs_table.php:16-24` | `id`, `connection`, `queue`, `payload`, `exception`, `failed_at` | Queue failure log |
| branches | `2020_09_01_090148_create_branches_table.php:16-21` | `id`, `name` | Has many studios (`studios.branch_id`, `2020_09_01_091433_create_studios_table.php:25`) |
| studios | `2020_09_01_091433_create_studios_table.php:16-27` | `id`, `branch_id`, `basic_price`, `aditional_friday_price`, `aditional_saturday_price`, `aditional_sunday_price` | Belongs to branch; has many schedules (`schedules.studio_id`, `..._create_schedules_table.php:26`) |
| movies | `2020_09_01_091535_create_movies_table.php:16-29` | `id`, `name`, `minute_length`, `picture_url` | Has many schedules; non-MySQL FK added in this migration (`:25-28`) |
| schedules | `2020_09_01_091503_create_schedules_table.php:17-32` | `id`, `movie_id`, `studio_id`, `start`, `end`, `price` (decimal 13,2) | Belongs to movie and studio; MySQL FK added here (`:28-30`) |
| dummies | `2020_09_05_083003_create_dummies_table.php:16-24` | `id`, `firstname`, `lastname`, `start`, `end` | Scratch table for overlap testing |
| oauth_* (Passport) | vendor package migrations | `oauth_access_tokens`, `oauth_clients`, `oauth_personal_access_clients`, `oauth_refresh_tokens` (typical Passport 9 set) | No in-repo migration; registered via `Passport::routes()` (`app/Providers/AuthServiceProvider.php:28`) |

Gaps that matter:

- **Schema drift on `studios.name`.** It is written by `StudioController@store/@update`
  (`app/Http/Controllers/StudioController.php:40,83`) and selected by
  `ScheduleController@index` (`app/Http/Controllers/ScheduleController.php:22`), and it
  is in `app/Studio.php:8` `$fillable`, but the `studios` migration defines no `name`
  column (`2020_09_01_091433_create_studios_table.php:17-24`). An install that runs only
  these migrations will fail those writes and queries. Whether the workspace PostgreSQL
  schema has the column is `UNKNOWN — requires human input`.
- **Driver-dependent foreign key.** `schedules.movie_id` is created in
  `..._create_schedules_table.php:28-30` for MySQL and in
  `..._create_movies_table.php:25-28` for non-MySQL; the workspace PostgreSQL install
  takes the non-MySQL branch.
- **Passport OAuth tables** have no in-repo migration or dump; the exact table set and
  route count registered at runtime are `UNKNOWN — requires human input`.
- `dummies.lastname` exists but is never read or written — a harmless dead column.
- No domain table lacks a migration, and none was reconstructed from queries.

## Auth

Mechanism: Laravel Passport v9.3.2 OAuth2 personal-access tokens
(`composer.json:16`, `composer.lock:1162-1163`; `HasApiTokens` on `app/User.php:9`). The
API guard is `driver => passport` (`config/auth.php:37-40`). Protected routes use
`['middleware' => 'auth:api']` (`routes/api.php:24`), which resolves to
`App\Http\Middleware\Authenticate` (`app/Http/Kernel.php:52`). Public `api/v1` endpoints
— register, login, the public schedule list and filter, all branches, all studios — sit
outside that group (`routes/api.php:18-23`). There is no auth gate on global middleware,
so a headerless probe can reach `/`, the public `api/v1` endpoints and the three
unprefixed dummy routes; everything under `routes/api.php:24-52` is 401'd when
unauthenticated.

Login is `Auth::attempt(['username' => ..., 'password' => ...])`
(`app/Http/Controllers/AuthController.php:34-44`, token returned at `:38`); registration
is at `:13-30`. Password reset has config (`config/auth.php:~90-96`, `passwords.users` →
`password_resets`) but no routes or controllers, so it is effectively absent. Two
`UNKNOWN — requires human input` items: whether `Auth::attempt` on the session `web`
guard works for the API login route without session state in production, and what the
`users.role` (`admin|user`) split is meant to authorize — no code reads or enforces it.

## External services

No third-party HTTP APIs are called from controllers or providers. Passport is a
first-party OAuth server; `guzzlehttp/guzzle` is locked but unused in app code. What is
integrated, with the env var *names* that configure it (values are never recorded here):

- **DB** — code default `mysql` (`config/database.php:16`); `pgsql` and `sqlite`
  connections are also defined (`config/database.php:36-95`). The image installs both
  `pdo_mysql` and `pdo_pgsql` (Dockerfile:6,18), and migrations branch on
  `DB::getDriverName()`. Names: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
  `DB_USERNAME`, `DB_PASSWORD`, `DB_SOCKET`, `DB_FOREIGN_KEYS`, `DATABASE_URL`,
  `MYSQL_ATTR_SSL_CA`.
- **Cache** — `file` (`CACHE_DRIVER`, `config/cache.php:21`).
- **Session** — `file`, lifetime 120 (`SESSION_DRIVER`, `SESSION_LIFETIME`,
  `config/session.php:21,34`).
- **Queue** — `sync` (`QUEUE_CONNECTION`, `config/queue.php:16`); `failed_jobs` exists but
  no job is dispatched.
- **Mail** — `smtp` (`MAIL_MAILER`, `config/mail.php:16`); no mail is sent in code.
- **Filesystem** — `local` (`FILESYSTEM_DRIVER`, `config/filesystems.php:16`);
  `movies.picture_url` is a plain URL string, not an upload, and there is no upload
  endpoint.
- **Broadcast** — `log` (`BROADCAST_DRIVER`, `config/broadcasting.php`); Pusher config
  exists but Echo is commented out.

## Operational risks

- **Build-time boot** — `RUN php artisan storage:link` (Dockerfile:13) boots Laravel
  during the image build. It is safe now because `AppServiceProvider::boot()` is empty
  (`app/Providers/AppServiceProvider.php:18-21`), but any provider that starts querying
  the DB would break the build.
- **DB driver mismatch** — code and config default to MySQL while the workspace schema is
  PostgreSQL `masch-backend-db`. Runtime depends on `DB_CONNECTION`/`DB_HOST` overrides
  and both PDO drivers being present; without `DB_CONNECTION=pgsql` the app tries MySQL,
  which is absent from the cluster. `CONTAINERIZE.md` skipped DB smoke for this reason.
- **EOL base** — PHP 7.2 (`Dockerfile:2,15`), flagged for human review.
- **File-backed state** — `file` session and cache do not survive more than one replica or
  a pod restart (`config/session.php:21`, `config/cache.php:21`).
- **Schema drift** — the `studios.name` gap above.
- **Ephemeral filesystem** — `storage/` and `bootstrap/cache` are created and chowned at
  build (`Dockerfile:29-30`) but live in the container filesystem; no volume is declared.
- **No worker needed** — queue is `sync`, `failed_jobs` exists, no jobs dispatched, and
  the scheduler is empty (`app/Console/Kernel.php:25-27`); no cron or queue worker runs.
- **Per-pod throttling** — `throttle:60,1` (`app/Http/Kernel.php:45`) uses the default
  `file` cache store, so rate-limit counters are per pod.
- **Hardcoded credentials** — none in app source; only `env()` references in `config/*`.
- **Above one replica** — file session/cache, local `storage/`, and per-pod throttle
  counters all break; no shared storage or Redis is configured for this app.

## Container layout

Two stages: the composer binary is copied from `composer:2`; vendor is built on
`php:7.2-cli-alpine` with `mbstring pdo_mysql pdo_pgsql` (Dockerfile:2-12); the runtime
is `php:7.2-fpm-alpine` with nginx, supervisor, gettext and curl (Dockerfile:15-21). The
process runs as `www-data` (Dockerfile:32), and the php-fpm pool sets `clear_env = no`
(`.containerize/php-fpm-pool.conf:8`) so runtime env injection reaches the app. The
image is generated from the `Dockerfile.php-fpm` template; `.dockerignore` excludes
`.env`, `.smoke.env`, vendor, node_modules and build directories (`.dockerignore:1-30`).

Repo-local fixes: none documented beyond the generated files. The repo is committed on
`chore/containerize` with local PostgreSQL-enablement commits (`git log`: `4d03466`
"chore: enable PostgreSQL runtime", `89717ae` "chore: merge containerization and
PostgreSQL migration"). It is **not** one of the six never-re-render repos; it re-renders
reproducibly from `Dockerfile.php-fpm`. `CONTAINERIZE.md` holds the load-bearing flags
(PHP 7.2 EOL base, composer platform-req workaround, Jenkins agent label) rather than
restating them here.

## Open questions

These are unresolved in the report and marked `UNKNOWN — requires human input`:

- The exact meaning of the project name "masch" — no expansion appears in source.
- The intended use of `users.role` (`admin|user`) — nothing enforces it.
- Whether the workspace PostgreSQL `masch-backend-db` schema includes the `studios.name`
  column missing from the migration.
- The exact set and count of Passport OAuth tables and routes registered at runtime.
- Whether `Auth::attempt` works for the API login route without session state.
- Which DB connection (`mysql` vs `pgsql`) a real deployment targets, and the real
  values of `DB_*`, `APP_KEY`, `DB_CONNECTION` in a real `.env` (never read here).
