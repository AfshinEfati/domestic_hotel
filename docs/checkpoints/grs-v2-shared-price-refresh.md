# GRS V2 — independent SSP-driven price and inventory refresh

Updated: 2026-09-19. Branch: `feature/reservation-foundation`.

## Ownership and isolation

- `provider:city grs` remains unchanged and owns city synchronization.
- `grs:sync-hotels` / queue `grs-hotels` own the weekly GRS hotel catalog and mappings.
- `grs:sync-prices` / queue `grs-prices` own GRS availability only. No city/catalog sync and no other suppliers.
- Legacy price and generic room commands still exist but their previous automatic scheduling is paused. Do not run legacy default price workers in parallel during the initial rollout.

## Required SSP schema and connection

SSP owns `hotel_price_refresh_schedules`. Add `grs_id` (nullable string, indexed; provider's property ID) and `next_gds_run_at` (nullable indexed timestamp) in the SSP-owning project, then populate verified mapping IDs. SSP `hotel_accommodation_id` is not the local GDS accommodation ID. GDS does not migrate SSP tables. It checks both columns before contacting GRS.

Only the database **connection credentials** live in `.env`: `DB_HOST_SHARE`, `DB_DATABASE_SHARE`, `DB_USERNAME_SHARE`, `DB_PASSWORD_SHARE` and optional `DB_PORT_SHARE`. `shared_ssp` is registered separately from the local GDS connection. SSP owns its existing last-run, error and lock fields. GDS modifies only `next_gds_run_at`.

## Admin-configurable refresh options (providers.config JSON)

For an existing provider, run **only** `php artisan db:seed --class=GrsPriceRefreshConfigSeeder` to populate missing defaults. This dedicated seeder requires an existing GRS provider and never creates, edits or deactivates other suppliers. `ProviderSeeder` also includes the defaults when creating a fresh GRS provider but may alter legacy supplier states and is NOT the recommended production backfill command. Both seeders preserve existing GRS administrator overrides and its online state. The existing provider Admin update service recursively merges JSON changes.

The GRS JSON contains the following `price_refresh` object:

```json
{
  "price_refresh": {
    "default_days": 90,
    "dispatch_limit": 10,
    "claim_minutes": 15,
    "failure_backoff_minutes": 15,
    "api_cooldown_minutes": 15,
    "scheduler_enabled": false
  }
}
```

Admins edit this object through the existing provider configuration API. Values are read directly from `providers.config` on each scan/worker execution and each scheduler tick; missing or invalid keys use defaults from `GrsRefreshSettings`. The existing `availability_rate_limit.max_requests` and `availability_rate_limit.window_minutes` in the same provider JSON control BOTH availability and supplementary room metadata requests. The safety cap of 10 requests/window remains.

No `GRS_PRICES_DISPATCH_LIMIT`, `GRS_PRICES_SCHEDULER_ENABLED` or operational `config/grs.php` values are used. `config/grs.php` contains connection details only.

## Scheduling and priority

- `php artisan grs:sync-prices` uses current `price_refresh.default_days` (90 if missing); `--days=30` overrides the range for that dispatch. `SyncGrsDuePricesJob::dispatch(30)` works too. Allowed override: 1–3650 days.
- The minute scheduler is enabled only when `price_refresh.scheduler_enabled=true`. The seeded default is false until shared schema and integration are ready; changing it through Admin takes effect on subsequent scheduler evaluations.
- The selector takes active, mapped, due SSP rows (`next_gds_run_at <= SSP clock`, or null) ordered oldest first. It claims up to `price_refresh.dispatch_limit` rows with compare-and-swap and a temporary deadline set by `price_refresh.claim_minutes`.
- On verified persistence the deadline advances by the row's current SSP `refresh_interval_minutes`. Failures back off using `failure_backoff_minutes`. HTTP 429 sets a global cooldown of at least `api_cooldown_minutes` (or a longer Retry-After); quota deferrals retain jobs instead of marking success.
- A missing local accommodation map causes no provider HTTP and is moved an hour later for investigation.

## API quota and mapping fallback

`RateLimitedGrsAdapter` wraps both availability and conditional room metadata requests. Both consume the same GRS quota; the secondary request is NOT removed. Cache must be shared/persistent across workers (`database` or Redis) and the queue asynchronous. The V2 command rejects unsafe sync queues/unshared caches. Do not run an unmetered legacy worker concurrently.

`HotelSyncService` performs the existing room/rate-plan mapping fallback. V2 surfaces swallowed supplementary failures and validates that response dimensions map to the right local hotel and calendar rows were freshly persisted before advancing the SSP deadline. Empty responses are not declared successful.

## Safe rollout

1. Deploy/populate SSP `grs_id` and `next_gds_run_at`. Configure only shared DB credentials in `.env`.
2. Run `php artisan db:seed --class=GrsPriceRefreshConfigSeeder`; edit `providers.config.price_refresh` through the existing Admin provider API. Keep `scheduler_enabled=false` and use `dispatch_limit=1` for the first live run.
3. Run `php artisan test --filter=GrsPriceRefreshV2Test` and `php artisan test --filter=GrsProviderSeederTest`. Stop the old default price worker.
4. Manually dispatch `php artisan grs:sync-prices --days=30`, then run only `php artisan queue:work --queue=grs-prices --sleep=3` in another terminal. Avoid `--stop-when-empty` while delayed jobs remain.
5. Inspect `room_calendars`, mapping completeness, `next_gds_run_at`, logs and 429 handling. Dispatcher DONE alone does not prove successful per-hotel refresh.
6. After validation enable `price_refresh.scheduler_enabled=true` through Admin. No `.env` or code edit is needed.

## Boundaries

- GDS does not own SSP migrations, nor was production SSP/GRS accessed during implementation.
- Historical calendar rows with mismatched `provider_property_id` may need separate reconciliation rather than being reported successful.
- Other suppliers and the old commands/jobs are not changed by this refactor.
