# Eagle Global Hub LTD Platform

Development follows the approved Laragon Local Development → Staging → Production workflow.

## Current delivery baseline

- Framework: Laravel 13 on PHP 8.3
- Integration branch: `develop`
- Health endpoint: `/up`
- Production configuration check: `php artisan app:production-readiness`
- Deployment procedure: [`docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md`](docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md)

Do not commit `.env` or production credentials.
