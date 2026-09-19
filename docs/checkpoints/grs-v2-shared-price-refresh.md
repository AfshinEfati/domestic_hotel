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

Run `php artisan db:seed --class=ProviderSeeder` to populate **missing** defaults. Rerunning the seeder preserves administrator overrides, other JSON keys and GRS online status. The existing provider Admin update service recursively merges JSON changes; changing one key does not wipe unrelated provider settings.

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

Admins edit this object via the existing provider configuration API/panel, with no PHP or `.env` changes. Values are read directly from `providers.config` on each scan/worker execution and each scheduler tick. Missing or invalid keys use defaults from `GrsRefreshSettings`. The original `availability_rate_limit.max_requests` and `availability_rate_limit.window_minutes` in this same provider JSON control **both** availability and supplementary room metadata requests. An internal safety cap of 10 requests/window is retained.

No `GRS_PRICES_DISPATCH_LIMIT`, `GRS_PRICES_SCHEDULER_ENABLED` or operational `config/grs.php` values are used. `config/grs.php` contains connection details only.

## Scheduling and priority

- `php artisan grs:sync-prices` uses the current `price_refresh.default_days` (90 if missing); `--days=30` overrides it for that dispatch. Direct `SyncGrsDuePricesJob::dispatch(30)` works. Allowed override 1–3650 days.
- The minute scheduler is enabled only when `price_refresh.scheduler_enabled` is true. The seeded default is false until shared schema and integration are ready; changing it in Admin takes effect on subsequent scheduler evaluations.
- The selector takes active, mapped, due SSP rows (`next_gds_run_at <= SSP clock`, or null) ordered oldest first. It claims up to `price_refresh.dispatch_limit` rows with compare-and-swap; the temporary deadline comes from `price_refresh.claim_minutes`.
- On verified persistence the deadline advances by the row's current `refresh_interval_minutes` from SSP. On failure it backs off by `failure_backoff_minutes`. GRS HTTP 429 sets a global cooldown of at least `api_cooldown_minutes` (or a longer Retry-After). Quota deferrals retain the job instead of claiming success.
- A missing local accommodation map causes no provider HTTP and is moved an hour later for investigation.

## API quota and mapping fallback

`RateLimitedGrsAdapter` wraps both availability and conditional room metadata requests. Both consume the same GRS quota; the secondary request is **not removed**. Cache must be shared/persistent across workers (`database` or Redis) and the queue asynchronous. The V2 command rejects an unsafe sync queue or unshared cache. A second unmetered worker must not run concurrently.

The existing `HotelSyncService` performs room/rate-plan mapping fallback. V2 catches swallowed supplementary failures and validates that returned dimensions map to the correct local hotel and that calendar rows were freshly persisted before advancing the SSP deadline. An empty response is not declared successful.

## Safe rollout

1. Deploy and populate SSP `grs_id` and `next_gds_run_at`; configure only shared DB credentials in `.env`.
2. Run `php artisan db:seed --class=ProviderSeeder`; inspect/edit `providers.config.price_refresh` in Admin. Keep `scheduler_enabled=false` and use `dispatch_limit=1` for the first live run.
3. Run `php artisan test --filter=GrsPriceRefreshV2Test`. Ensure the old default price worker is stopped.
4. Manually dispatch `php artisan grs:sync-prices --days=30`, then run only `php artisan queue:work --queue=grs-prices --sleep=3` in another terminal. Do not use `--stop-when-empty` while delayed jobs remain.
5. Check `room_calendars`, map completeness, `next_gds_run_at`, logs and 429 response handling. The dispatcher DONE line is not proof of successful property refresh.
6. Enable `price_refresh.scheduler_enabled=true` through Admin only after validation. No `.env` or code edit is required.

## Boundaries

- GDS does not own SSP table migration, nor does it have access to the production shared DB or real GRS API in this implementation session.
- The existing calendar repository does not repair inconsistent historical `provider_property_id` in conflicting rows; reconcile those separately rather than marking a partial import successful.
- Other suppliers and the old commands/jobs are not changed by this refactor.
