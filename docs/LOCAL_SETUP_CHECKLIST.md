# MetaFore OTA — Local Setup Verification Checklist

Run these checks on the Windows/Laragon workstation before application development:

1. Laragon Full installed and running.
2. PHP version compatible with the locked Laravel version.
3. Composer available (`composer --version`).
4. Node.js and npm available (`node -v`, `npm -v`).
5. MySQL/MariaDB running and a dedicated local database created.
6. Git available and repository access verified.
7. Project path is `C:\laragon\www\metafore-ota`.
8. Local host resolves as `http://metafore-ota.test` (or HTTPS if Laragon SSL is enabled).
9. `.env` is excluded from Git.
10. Only after all checks pass: install dependencies, generate key, migrate/seed, link storage, build assets, clear caches, and run smoke tests.
