# Production Deployment Checklist

This checklist prepares a reviewed commit for staging and production without
activating travel providers or enabling live flight order execution.

## Release invariants

- Deploy an immutable artifact built from the reviewed commit. Do not rebuild
  or modify an already locked release.
- Keep `HOTELS_ENABLED`, `TOURS_ENABLED`, and `VISA_ENABLED` set to `false`.
- Keep `HOTEL_PROVIDER`, `TOUR_PROVIDER`, and `VISA_PROVIDER` set to
  `unavailable`.
- Keep `FLIGHT_ORDER_HTTP_EXECUTION_ENABLED` and
  `DUFFEL_LIVE_ORDER_CREATION_ENABLED` set to `false`.
- Store production credentials only in the deployment platform's encrypted
  secret store. Never copy them into the repository or build artifact.

## Build artifact

Run these commands on a clean checkout of the reviewed commit:

```powershell
composer install --prefer-dist --no-interaction
npm.cmd ci
php artisan test --compact
npm.cmd run build
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

Package the application only after the test suite and frontend build pass.
Exclude `.env`, `.git`, `node_modules`, tests, local logs, and local cache files.

## Production environment

Provision a fresh production `.env` through the deployment platform. At a
minimum:

- Set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, and an HTTPS
  `APP_URL`.
- Use persistent session and cache stores and an asynchronous queue connection.
- Configure a real mail transport rather than `log` or `array`.
- Configure the production database, log destination, filesystem, queue worker,
  scheduler, TLS termination, backups, and monitoring outside the repository.
- Preserve all provider and flight-execution safety values listed under
  **Release invariants**.

After environment values are injected, run the non-mutating guardrail:

```powershell
php artisan app:production-readiness
```

Every check must report `PASS`. The command prints no credential values and
does not contact providers, write to the database, or change application state.

## Controlled deployment

1. Confirm a restorable database backup and record the reviewed commit SHA.
2. Review pending migrations and their rollback implications before the change
   window. Run migrations only through the approved deployment process.
3. Place the application in maintenance mode only when the release requires it.
4. Switch the web root or release symlink to the immutable artifact.
5. Cache configuration, routes, and views after the production environment is
   present.
6. Restart long-running queue workers so they load the new release.

## Smoke verification

- Confirm `GET /up` returns HTTP 200 through the production load balancer.
- Confirm the home, authentication, and public information pages render over
  HTTPS without mixed-content or console errors.
- Confirm hotels, tours, and visa remain unavailable and no live supplier order
  request can be initiated.
- Confirm application and infrastructure logs contain no new errors.
- Verify queue processing, mail delivery, backups, and monitoring alerts through
  the platform's approved non-customer-impacting checks.

Record the commit SHA, artifact identifier, deployment time, operator, readiness
output, smoke-test results, and rollback decision in the release record.

## Rollback boundary

Rollback switches traffic to the previous immutable application artifact. Do
not run destructive database rollback commands. If a migration is not backward
compatible, stop and follow the migration-specific recovery plan approved
before deployment.
