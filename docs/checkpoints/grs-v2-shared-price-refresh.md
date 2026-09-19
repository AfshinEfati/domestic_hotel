# GRS V2 — SSP-driven price and inventory refresh

Updated: 2026-09-19. Branch: `feature/reservation-foundation`.

## Scope and ownership

- `provider:city grs` remains the separate city synchronizer.
- `grs:sync-hotels` / `grs-hotels` own weekly GRS catalog and mappings.
- `grs:sync-prices` / `grs-prices` own the availability refresh only. No catalog or city syncing on this path.
- The SSP application owns `hotel_price_refresh_schedules` and its migrations. GDS must not create or migrate SSP tables.
- `shared_ssp` is an application-wide connection defined once in `config/database.php`, not a GRS-specific connection. Only shared DB credentials live in `.env` (`DB_HOST_SHARE`, `DB_PORT_SHARE`, `DB_DATABASE_SHARE`, `DB_USERNAME_SHARE`, `DB_PASSWORD_SHARE`).

## SSP Model, Repository and Service

- `App\Models\HotelPriceRefreshSchedule`: explicit `shared_ssp` connection, SSP table name, no Eloquent timestamps.
- `App\Domain\Hotel\Repositories\HotelPriceRefreshScheduleRepository`: the sole accessor for selecting due records and recording GDS timestamps.
- `App\Domain\Hotel\Services\GrsPriceRefreshScheduleService`: selects one batch based on the provider's existing HTTP capacity and exposes the repository's lifecycle operations.
- Required SSP columns: `grs_id`, `next_gds_run_at`, `last_gds_success_run_at`, `last_gds_success_at`; additionally the existing `id`, `is_active`, and `refresh_interval_minutes` are used. The first four are checked before dispatch. `hotel_accommodation_id` is an SSP ID, NOT the local GDS accommodation ID.

## Administrator settings and seeding

Use the existing `database/seeders/ProviderSeeder.php`, already included in `DatabaseSeeder`; do not create a separate seeder. Existing provider settings, token and online status are preserved. Re-running the seeder removes the three unapproved legacy keys `dispatch_limit`, `claim_minutes`, `failure_backoff_minutes` from the GRS `price_refresh` JSON.

Approved `providers.config.price_refresh` defaults:

```json
{
  "default_days": 90,
  "api_cooldown_minutes": 15,
  "scheduler_enabled": false
}
```

Missing or invalid keys use defaults through `GrsRefreshSettings`. The existing `availability_rate_limit.max_requests` (default 10, hard ceiling 10) and `availability_rate_limit.window_minutes` (default 1) are the ONLY provider request-capacity settings. The existing provider Admin API can update the JSON without modifying PHP or `.env`.

## Selection, request limits and exact timestamp semantics

1. The minute scheduler runs only when the current GRS provider JSON has `price_refresh.scheduler_enabled=true`. A manual `php artisan grs:sync-prices --days=30` is also available; absent override uses configured 90-day default.
2. One query selects up to the existing GRS request capacity (10 by default) from active, due SSP schedules with a GRS ID, sorted **only** by `next_gds_run_at ASC, id ASC`. There is no separate hotel dispatch limit, random order, per-row polling, claim, artificial fairness sorting, or failure backoff.
3. These selected hotels are queued together as separate per-property jobs. Neither selection nor queue dispatch modifies `next_gds_run_at`. Existing per-property job uniqueness and the shared HTTP rate limiter remain technical safeguards; they do not modify the schedule.
4. On acquiring quota and starting the availability request, set `last_gds_success_run_at` using the SSP database clock. When GRS returns a successful HTTP response (normally HTTP 200), set `last_gds_success_at` even if subsequent mapping or persistence fails. This SSP-owned field name is preserved as supplied.
5. The existing availability service performs the conditional supplementary room/rate-plan mapping request when missing. Both network calls are metered separately; the normal production planning assumption is **10 hotels = 10 HTTP requests**.
6. Only after mapping verification and successful calendar persistence is `next_gds_run_at` advanced using the shared row's **current** `refresh_interval_minutes`. No other path advances or defers the due time: errors, 429, absent maps, quota exhaustion and empty availability leave it untouched.
7. `api_cooldown_minutes` remains: HTTP 429 pauses provider requests via shared cache, honoring a longer `Retry-After` when supplied. The due time is still not moved.

For 50 overdue, mapped hotels with one HTTP request each and successful persistence, five consecutive minute ticks can select ten hotels per tick. If the earliest due hotels keep failing or processing lags, they can remain at the head of subsequent selections: no unrequested priority changes or backoff were introduced.

## Verification and rollout

1. Populate the four GDS-used columns in the **SSP-owned** table and configure the general `shared_ssp` connection. Do not run migrations against the shared database from GDS.
2. For a disposable local test database only: `php artisan migrate:fresh --seed`. For an existing local database: `php artisan db:seed --class=ProviderSeeder`.
3. Run `php artisan test --filter=GrsProviderSeederTest` and `php artisan test --filter=GrsPriceRefreshV2Test`. Tests exercise approved config keys, idempotent seeding, ten-of-fifty selection without deadline writes, and the timestamp lifecycle.
4. Keep `scheduler_enabled=false` for a manual initial run: `php artisan grs:sync-prices --days=30`, then `php artisan queue:work --queue=grs-prices --sleep=3`. Inspect `room_calendars`, the two new timestamp columns and `next_gds_run_at`; do not treat the selector being DONE as proof of successful persistence.
5. After local validation, the administrator can enable `price_refresh.scheduler_enabled` through the existing provider API.

Laravel tests, the live SSP database and GRS production HTTP have NOT been executed from this GitHub-only change environment.
