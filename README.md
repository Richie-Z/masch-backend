# masch — cinema screening schedules API

masch is the JSON API behind a cinema's screening schedules. It stores the chain of
branches, studios, movies and showtimes, works out each showtime's end time from the
movie's length, and sets ticket prices that differ on weekdays, Friday, Saturday and
Sunday. Cinema staff maintain the catalogue and schedule through the paired CinemaXIX
browser client, while moviegoers read the public schedule endpoints. The API checks
new showtimes for overlaps before saving them.

Every showtime belongs to a movie and a studio, and every studio belongs to a branch.
Browsing endpoints are open; catalogue changes need an OAuth token.

## What you can do

- Register an account and log in to receive an API token (`POST /api/v1/register`,
  `POST /api/v1/login`).
- Read the public screening schedule and filter it by your own criteria
  (`GET /api/v1/user/schedule`, `POST /api/v1/schedule/filter`).
- List all branches and studios so a client can fill its pickers
  (`GET /api/v1/branches`, `GET /api/v1/studios`).
- Add, edit and remove cinema branches (`api/v1/branch`).
- Add, edit and remove studios, each carrying a base price and day-specific extras
  (`api/v1/studio`).
- Maintain the movie catalogue, including a full listing of every movie
  (`api/v1/movie`, `api/v1/movies`).
- Create and manage showtimes; the API computes end times, applies day-based prices and
  rejects overlaps (`api/v1/schedule`).
- Read your account details and revoke the current token (`AuthController@details`,
  `AuthController@logout`, routes/api.php:25-26).

## How it works

The service is a Laravel 7.28.1 JSON API using Passport OAuth2 personal-access
tokens. nginx and PHP-FPM run together in one container under supervisord and listen on
port 8080; the PHP runtime is 7.2, which is past end of life. Two route surfaces do the
work: open browsing and auth endpoints under `api/v1`, and Passport-protected CRUD
behind the `auth:api` guard. One detail worth knowing is that the `schedules.movie_id`
foreign key is declared in a different migration depending on the database driver, so
MySQL and PostgreSQL installs lay out the same relationships differently.

## Running it

Install the PHP dependencies with the same steps the image uses:

```
composer install --no-dev --no-scripts --no-autoloader
composer dump-autoload --optimize --no-dev
```

The documented way to start the built image is:

```
podman run --rm --network=host --env-file <env-file> -e PORT=<free-port> containerize/masch-backend:local
```

Supply `APP_KEY` and the `DB_*` variables at runtime. The code defaults to MySQL, which
is absent from the workspace cluster; point `DB_CONNECTION` at `pgsql` to use the
migrated `masch-backend-db` PostgreSQL schema. A bundled `npm run production` command
exists but builds front-end assets the API does not serve, so the image skips it.

## Limitations

- The `users.role` column distinguishes `admin` from `user`, but no code reads or
  enforces that split, so any token holder can reach the protected CRUD routes.
- Studio controllers write and read a `studios.name` column that the studio migration
  never defines; on a database built only from these migrations those operations fail.
  Whether the workspace schema has the column is unknown.
- Login uses `Auth::attempt` on the session guard while tokens come from Passport;
  whether it works without session state in production is unknown.
- This repo serves no app UI. The only web page is the stock Laravel welcome screen, so
  there are no product screenshots; all domain screens live in the paired CinemaXIX
  client.

Developer notes — exact lockfile versions, the full data model, Passport auth details
and operational risks — are in [TECHNICAL.md](TECHNICAL.md).
