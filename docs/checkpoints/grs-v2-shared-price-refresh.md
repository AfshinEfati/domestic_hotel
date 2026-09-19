# GRS V2 — SSP-driven price and inventory refresh

Updated: 2026-09-19. Deployment branch: `main`.

## Scope and ownership

- `provider:city grs` remains the separate city synchronizer.
- `grs:sync-hotels` queues the GRS catalog once, then dispatches `grs-hotels` database-only mapping batches.
- `grs:sync-prices` / `grs-prices` own the availability refresh only; no city or catalog sync on this path.
- The SSP application owns `hotel_price_refresh_schedules` and its migrations. GDS must not create or migrate SSP tables.
- `shared_ssp` is the application-wide shared connection from `config/database.php`. Only shared DB credentials belong in `.env` (`DB_HOST_SHARE`, `DB_PORT_SHARE`, `DB_DATABASE_SHARE`, `DB_USERNAME_SHARE`, `DB_PASSWORD_SHARE`).

## Critical identifier contract

The actual SSP table has an unsigned nullable **`gds_id`**, not `grs_id`.

```text
SSP.hotel_price_refresh_schedules.gds_id
  = local GDS accommodations.id
    -> accommodation_provider_maps.accommodation_id
       constrained by provider_id for providers.code = grs
       -> accommodation_provider_maps.provider_property_id
          = GRS property ID sent to /v1/available-rooms and /v1/properties/{id}
```

`hotel_accommodation_id` identifies an SSP hotel, NOT the GDS accommodation ID. Never use `gds_id` as the provider property ID. The dispatcher passes the numeric GDS ID into the worker; the worker independently looks up the GRS mapping before its HTTP request. Calendar verification uses both local `accommodation_id = gds_id` and `provider_property_id = grs property ID`.

`App\Models\HotelPriceRefreshSchedule` selects the `shared_ssp` connection with no automatic timestamps. `HotelPriceRefreshScheduleRepository` owns due selection and SSP timestamp writes, while `GrsPriceRefreshScheduleService` handles the GRS provider quota policy. Required shared columns checked by the repository: `gds_id`, `next_gds_run_at`, `last_gds_success_run_at`, `last_gds_success_at`. Also used: `id`, `is_active`, `refresh_interval_minutes`. No GDS migration for this SSP table.

## Settings and scheduling

Use existing `database/seeders/ProviderSeeder.php` (already included by `DatabaseSeeder`). Existing provider config and token are preserved. The approved `providers.config.price_refresh` defaults are:

```json
{
  "default_days": 90,
  "api_cooldown_minutes": 15,
  "scheduler_enabled": false
}
```

The legacy keys `dispatch_limit`, `claim_minutes`, `failure_backoff_minutes` are removed by the seeder. The existing `availability_rate_limit.max_requests` (default 10, hard ceiling 10) and `availability_rate_limit.window_minutes` (default 1) control request capacity. The Admin API can adjust the provider JSON.

`routes/console.php` evaluates `scheduler_enabled` each minute. If false, only the automated schedule is disabled; a manual `php artisan grs:sync-prices --days=30` still dispatches a job. The CLI message saying "queued" confirms dispatch only, not that the HTTP request or persistence succeeded.

## Selection, limits and timestamps

1. A due-scan job selects up to the provider's configured capacity (10 by default) from active, mapped-ID, due SSP rows, ordered by `next_gds_run_at ASC, id ASC`. An absent local provider map skips the selected row without changing its due time.
2. The scan dispatches per-GDS-accommodation price jobs. Selection and dispatch do NOT modify `next_gds_run_at`. Job uniqueness and the shared rate limiter prevent uncontrolled duplicate work; they do not advance the SSP due time.
3. The worker verifies the shared schedule and resolves the corresponding GRS provider property ID from its local map. Availability is fetched for the GRS ID only. When missing room/rate mappings require it, the existing service can send one supplementary property request; both calls consume the same API quota. Do not assume one hotel ALWAYS equals one HTTP request.
4. Once API quota is acquired, `last_gds_success_run_at` is set just before the availability request using the SSP DB clock. Successful HTTP response updates `last_gds_success_at`, even if subsequent persistence fails.
5. Only after verifying saved calendar dimensions does `next_gds_run_at` advance using that SSP row's current `refresh_interval_minutes`. Empty data, HTTP failures, 429, missing mappings, quota exhaustion or persistence failures leave the due time unchanged. HTTP 429 starts the configured shared cooldown (or longer `Retry-After`).
6. With 50 overdue hotels and 10 successful persisted hotel jobs per minute, the first five scans can process them in order. If earliest rows fail or are delayed, they remain due and can be selected again; no fairness, claim or backoff was introduced.

## Horizon and timeouts

`config/horizon.php` must listen to all existing queues: `default`, `grs-hotels`, `grs-prices`. In `main`, `supervisor-1` uses the Redis connection, `balance=auto`, max 10 processes in production, and retains its 60-second timeout. The dedicated GRS price worker has a 55-second timeout below that Horizon limit; Redis retry_after defaults to 90 seconds. **Any newly introduced named queue must be added to Horizon configuration in the same change.** Separate queues alone do not make jobs faster.

## Verification and rollout

1. Confirm `shared_ssp.hotel_price_refresh_schedules` has `gds_id` and the three GDS timestamp fields. Do NOT run migrations against the SSP database from GDS.
2. Run `php artisan test --filter=GrsPriceRefreshV2Test` against the disposable test database only. The fixture deliberately makes GDS IDs different from GRS property IDs and asserts the HTTP request sends the provider ID.
3. Verify `php artisan config:show horizon` lists both GRS queues, `php artisan horizon:status` reports running, and the running Horizon process has loaded the latest config. Reload its managed process through the deployment process if needed.
4. Keep `scheduler_enabled=false` while manually testing `php artisan grs:sync-prices --days=30`; this still selects up to the provider capacity, not exactly one hotel. Check `GRS due price scan finished` logs, individual price job logs, `room_calendars`, and SSP timestamps. A successful due-scan alone does not mean prices were persisted. Be careful with jobs left in Redis from older incompatible versions.
5. After validation, the administrator may enable `price_refresh.scheduler_enabled` through the provider API.

Automated tests and production HTTP/SSP integration have not been run from the GitHub connector environment; verify them in the project runtime before enabling automated price refresh.
