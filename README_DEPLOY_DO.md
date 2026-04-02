# DzungfHotel on DigitalOcean App Platform

## Env vars
- `APP_ENV=production`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`

## App setup
- Keep the app source at repo root. `index.php` is the entrypoint.
- App Platform should detect PHP automatically from `composer.json`.
- Do not add Docker. This repo is ready for buildpack deploy.

## Managed MySQL
- Create a DigitalOcean Managed MySQL cluster.
- Create a database named `dzungfhotel`.
- Import `database/dzungfhotel.sql` into that database.
- Copy the cluster connection values into the `DB_*` env vars above.

## Health endpoints
- App only: `/health.php`
- App + database: `/health-db.php`

## 10-step checklist
1. Push this repo to GitHub or GitLab.
2. Create a Managed MySQL cluster on DigitalOcean.
3. Create the database `dzungfhotel` in that cluster.
4. Import `database/dzungfhotel.sql`.
5. Create a new App Platform app from the repo.
6. Confirm DigitalOcean detects a PHP app from `composer.json`.
7. Set `APP_ENV=production` and all `DB_*` env vars.
8. Deploy, then open `/health.php` and `/health-db.php`.
9. Add your subdomain in App Platform and copy the target hostname.
10. In Namecheap, create a CNAME for the subdomain pointing to the App Platform target hostname.
