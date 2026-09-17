# GRS V2 — independent SSP-driven price and inventory refresh

Date: 2026-09-17. Branch: `feature/reservation-foundation`.

## Ownership and isolation

- `provider:city grs` is unchanged; it owns city synchronization.
- `grs:sync-hotels` / `grs-hotels` remain unchanged; they own the weekly GRS catalog and accommodation mappings.
- **New** `grs:sync-prices` / `grs-prices` own GRS price and inventory only. No city/catalog synchronization occurs here. No other provider uses these jobs.
- Legacy `hotel:sync`, `SyncGrsAvailabilityJob`, and `SyncGrsAvailabilityForPropertyJob` remain present, but their previous automatic scheduling stays disabled. Do not run the legacy default queue worker in parallel during the initial V2 rollout.

## Required shared SSP schema (must be deployed in the SSP-owning project)

The existing `hotel_price_refresh_schedules` table must have two additional fields:

- `grs_id`: nullable string (recommended 64 characters), provider's property ID, indexed. It is NOT `hotel_accommodation_id` from SSP and NOT the local GDS accommodation ID. Populate it from verified GRS mappings.
- `next_gds_run_at`: nullable indexed timestamp. Set to the time a hotel becomes eligible for its first GDS refresh. If null, the selector treats the row as due first.

This GDS code intentionally does not create or migrate the shared database table. It fails before calling GRS if the fields are missing. SSP retains ownership of `last_started_at`, `last_finished_at`, `last_success_at`, `last_failed_at`, `locked_at`, `locked_until`, `failure_count`, `last_error`, and `next_run_at`. The GDS code modifies ONLY `next_gds_run_at`.

Connection `shared_ssp` is registered by `AppServiceProvider` from `config/grs.php`; the config reads `DB_HOST_SHARE`, `DB_DATABASE_SHARE`, `DB_USERNAME_SHARE`, `DB_PASSWORD_SHARE` and optional `DB_PORT_SHARE` (3306). This connection never replaces the local default DB. Set credentials in `.env`, not in Git.

## Scheduling and priority

- Manual command `php artisan grs:sync-prices` dispatches the selector with default **90 days**.
- `php artisan grs:sync-prices --days=30` requests 30 days. Direct dispatch `SyncGrsDuePricesJob::dispatch(30)` is also supported. Allowed range is 1–3650 days.
- The cron tick runs every minute only if `GRS_PRICES_SCHEDULER_ENABLED=true`. It is **false by default** until testing and shared schema deployment are finished.
- The selector uses `shared_ssp.hotel_price_refresh_schedules`, considers only `is_active=1`, non-null `grs_id`, and `next_gds_run_at <= now()` (or null), sorts oldest `next_gds_run_at` then `id`, and claims up to 10 per tick by compare-and-swap on `next_gds_run_at`.
- The temporary claim advances `next_gds_run_at` by 15 minutes; if a job is lost the row becomes eligible again. Per-property `ShouldBeUnique` prevents duplicate queued jobs while the unique lock is held. A missing local accommodation/provider map incurs NO provider HTTP request and is moved one hour later for investigation.
- On verified persistence, the row's `next_gds_run_at` advances by its CURRENT `refresh_interval_minutes` from SSP (minimum one minute). Errors back off 15 minutes; provider HTTP 429 triggers a global 15-minute minimum cooldown (or longer numeric Retry-After). A quota deferral releases the job without claiming success.

## HTTP limit and mapping fallback

The V2 `RateLimitedGrsAdapter` wraps both `fetchAvailability` and the conditional `fetchRoomTypes` request. Both use the SAME rate-limit key as the legacy GRS path (`grs-availability`), with a hard cap of 10 requests per configured window. Every request is charged before sending, including HTTP failures. A shared cache lock serializes quota accounting across workers; production must use persistent shared `CACHE_STORE=database` or `redis` and an asynchronous queue. The V2 command rejects `QUEUE_CONNECTION=sync` and unshared cache settings. Do not run a parallel unmetered legacy/provider catalog process during rate testing.

Missing room/rate-plan maps continue to trigger the necessary secondary GRS request through the existing `HotelSyncService` mapping fallback. This request is NOT removed. The V2 adapter records any swallowed supplemental exception and V2 treats the hotel as failed/deferred, never as successful.

After the service returns, the property job validates that all provider room and rate-plan IDs from the response have local maps belonging to the right accommodation, and that the expected calendar dimensions were persisted with fresh `updated_at`. Only then does it advance the SSP refresh time as success. An empty or partially unmapped response is not reported as a successful refresh.

## Safe rollout

1. Deploy the SSP migration for `grs_id` and `next_gds_run_at` and populate valid mappings. Never assume SSP `hotel_accommodation_id` equals the local GDS `accommodations.id`.
2. Configure `.env` shared DB credentials, `QUEUE_CONNECTION=database` (or Redis), and `CACHE_STORE=database` (or Redis). Keep `GRS_PRICES_SCHEDULER_ENABLED=false` while validating.
3. `php artisan config:clear` (or rebuild config cache as appropriate); then run `php artisan test --filter=GrsPriceRefreshV2Test`.
4. Inspect shared due rows, local `accommodation_provider_maps`, room/rate-plan mappings, and provider rate limits. Ensure old default-queue price workers are stopped. Do not use `hotel:sync grs` for this workflow.
5. Manually dispatch: `php artisan grs:sync-prices --days=30` (or no days for 90). In another terminal start ONLY `php artisan queue:work --queue=grs-prices --sleep=3`. Do not use `--stop-when-empty` because quota-delayed jobs can remain in the queue.
6. Verify new `room_calendars` rows, timestamps and each affected shared row's `next_gds_run_at`, inspect logs for missing maps and 429. A dispatcher DONE message does not establish price-sync success.
7. Enable `GRS_PRICES_SCHEDULER_ENABLED=true` only after production-like test passes and restart workers / refresh config.

### Known limits / next work

- No live SSP database or provider API is available in this implementation session; only isolated test code has been added. The tests must be executed in your actual Laravel environment before rollout.
- One hotel returning zero valid availability rows is treated as a failed refresh because nothing was persisted; decide a separate explicit sold-out/empty-response policy if required by GRS semantics.
- The existing calendar repository does not rewrite `provider_property_id` on conflicting rows. Existing inconsistent historical rows may fail strict persistence verification and require a one-off reconciliation, not a fabricated success.
- This stage intentionally does not create equivalent paths for other suppliers or delete old commands/jobs.
