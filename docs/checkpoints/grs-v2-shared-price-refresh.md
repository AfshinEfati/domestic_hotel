# GRS V2 — independent SSP-driven price and inventory refresh

Updated: 2026-09-19. Branch: `feature/reservation-foundation`.

## Ownership and isolation

- `provider:city grs` owns city synchronization and is unchanged.
- `grs:sync-hotels` / `grs-hotels` own the weekly hotel catalog and mappings.
- `grs:sync-prices` / `grs-prices` own GRS price and inventory only; neither cities nor the catalog are synced by this path.
- Legacy automatic availability and generic room schedules remain paused pending validation.

## Shared SSP database: standard Laravel configuration

The `shared_ssp` connection belongs to the **entire application**, not to GRS. Its single definition lives in `config/database.php` under `connections.shared_ssp`. All consumers use `DB::connection('shared_ssp')`. No GRS-specific config file or `AppServiceProvider` dynamic connection registration is needed.

Only connection credentials are supplied via `.env`: `DB_HOST_SHARE`, `DB_DATABASE_SHARE`, `DB_USERNAME_SHARE`, `DB_PASSWORD_SHARE` and optional `DB_PORT_SHARE`. These are not operational provider settings. If config is cached after changing credentials, rebuild/clear the Laravel config cache. The ordinary default database connection is untouched.

SSP owns `hotel_price_refresh_schedules`. Add/populate `grs_id` (nullable string, indexed, the provider's property ID) and `next_gds_run_at` (nullable indexed timestamp) in the SSP-owning project. SSP `hotel_accommodation_id` must not be treated as local GDS `accommodations.id`. GDS does not migrate SSP tables and checks both columns before contacting GRS. SSP retains ownership of last-run, error and locking fields; the GDS job updates only `next_gds_run_at`.

## Provider config and seeding

There is **one** provider seeder: `database/seeders/ProviderSeeder.php`. It contains the GRS `price_refresh` defaults alongside existing provider configuration. This is already included by the existing `DatabaseSeeder` for a fresh/test database; no extra seeder exists. For an existing database use `php artisan db:seed --class=ProviderSeeder`. Re-running it fills missing GRS JSON keys while preserving admin overrides, provider activation, authentication tokens and other suppliers' online states.

`providers.config.price_refresh` defaults:

```json
{
  "default_days": 90,
  "dispatch_limit": 10,
  "claim_minutes": 15,
  "failure_backoff_minutes": 15,
  "api_cooldown_minutes": 15,
  "scheduler_enabled": false
}
```

The existing Admin provider API accepts changes to this JSON and merges them into the current provider configuration. `GrsRefreshSettings` reads it at each execution and provides defaults if keys are missing or invalid. The existing `availability_rate_limit.max_requests` and `availability_rate_limit.window_minutes` in the same JSON also meter both availability and necessary supplemental room/rate-plan requests. No provider operational settings belong in `.env` or a standalone PHP config file.

## Scheduling and priority

- `php artisan grs:sync-prices` uses current `price_refresh.default_days` (90 fallback). `--days=30` overrides the window for that dispatch; allowed range is 1–3650 days.
- The minute scheduler checks `price_refresh.scheduler_enabled` directly from the current provider JSON. The default is false until shared-schema deployment and tests are completed; an Admin change takes effect at the next scheduler evaluation.
- The selector takes active due SSP rows with a non-null `grs_id`, orders by oldest `next_gds_run_at` then ID, and uses compare-and-swap to claim up to `price_refresh.dispatch_limit` hotels. The temporary claim duration comes from `claim_minutes`.
- On verified calendar persistence, `next_gds_run_at` advances according to the shared row's latest `refresh_interval_minutes`. Failures back off using `failure_backoff_minutes`; HTTP 429 enables a shared cooldown using `api_cooldown_minutes` or longer Retry-After. Quota deferrals are not marked successful.
- Unmapped provider hotels make no provider HTTP request and are deferred for investigation.

## API quota and mapping fallback

The V2 adapter meters both availability and necessary supplemental room-metadata requests on the same quota. The existing mapping fallback is retained. A persistent shared cache (database/Redis) and asynchronous queue are required; do not run unmetered legacy workers concurrently. The property worker verifies mapping ownership and recently persisted calendar dimensions before recording success. Empty/unmapped responses are not treated as successful refreshes.

## Testing and rollout

1. Fresh/test DB: `php artisan migrate:fresh --seed` **only in an expendable test environment**. For an existing database, do not refresh it: run `php artisan db:seed --class=ProviderSeeder` instead.
2. Apply/populate the two SSP columns in its owning project and configure shared connection credentials in `.env`; run `php artisan config:clear` after connection changes if needed.
3. Run `php artisan test --filter=GrsProviderSeederTest` and `php artisan test --filter=GrsPriceRefreshV2Test`. Keep `price_refresh.scheduler_enabled=false` and set `dispatch_limit=1` through the Admin provider API for the first live test.
4. Manually dispatch `php artisan grs:sync-prices --days=30`, then run the separate queue worker `php artisan queue:work --queue=grs-prices --sleep=3`. Delayed jobs may remain in queue; do not interpret the dispatcher finishing as evidence of a successful per-hotel refresh.
5. Verify new `room_calendars` values, each SSP `next_gds_run_at`, map completeness, logs and 429 handling. Only after validation, enable `price_refresh.scheduler_enabled=true` through Admin.

The SSP and live GRS API were not available for execution during this code change; the Laravel tests need to run in the project environment before rollout.
