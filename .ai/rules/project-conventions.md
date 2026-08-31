# MetaFore OTA Project Conventions

These conventions apply to application-owned Laravel code in MetaFore OTA.
Framework/tooling-owned code such as Laravel or Fortify defaults does not establish an application convention unless explicitly adopted.

## Routing

- Use explicit/manual HTTP route declarations.
- Give application routes explicit names.
- Organize related routes with `prefix`, `name`, and `middleware` groups where appropriate.
- Keep authorization middleware visible at the route/group level.

## Authorization

- Protect authenticated application areas with route middleware.
- Admin routes should use the established combination of `auth`, `verified`, and Spatie role/permission middleware as appropriate.
- Use existing permission names such as `master-data.view` and `master-data.manage` for Master Data access.
- Controller-level Policies, Gates, or `authorize()` calls are not a project-wide default unless future evidence establishes that convention.

## Testing

- Write application tests as class-based PHPUnit tests extending the project `Tests\TestCase`.
- Test methods use the `test_...(): void` naming/signature style.
- Database-touching application tests should use `RefreshDatabase` unless a specific test requires a different isolation strategy.
- Keep assertions focused on observable behavior, authorization, validation, responses, and database state.

## Seeders

- Seeders must be safe to run repeatedly.
- Avoid duplicate accumulation across repeated seeding.
- Use an idempotent operation appropriate to the case, such as `firstOrCreate`, `syncRoles`, `syncPermissions`, or `insertOrIgnore`.
- Eloquent and Query Builder are both acceptable; no universal seeder data-access style has been established.

## Settings Regression Guardrail

- `SettingService::get()` must cache a plain payload containing the setting `value` and `type`.
- Never cache an Eloquent `Setting` model from `SettingService::get()`.
- Existing Settings backend routes, management UI behavior, permissions, and cache behavior must not regress.
