# Domestic Hotel — GRS price refresh checkpoint

Updated: 2026-09-19. Working branch: `feature/reservation-foundation`, fast-forwarded from `main` at `e6e2b9a`. Do not merge without the user's instruction.

## Identifier contract

`shared_ssp.hotel_price_refresh_schedules.gds_id` identifies OUR `accommodations.id`. `hotel_accommodation_id` is the separate SSP ID. The GRS-specific worker resolves `(provider_id, accommodation_id = gds_id)` through `accommodation_provider_maps` and sends ONLY the mapped `provider_property_id` to the GRS API. SSP migrations are owned by SSP, never GDS.

## Scheduling and provider separation

`routes/console.php` is declarative. The minute event invokes `ProviderPriceRefreshScheduler::dispatch()`, not a DB query. The scheduler uses the existing `ProviderRepositoryInterface::getAll()` ONE time per minute, then resolves registered `PriceRefreshSchedulerHandler` implementations by provider code. Currently only `GrsScheduledPriceRefresh` is registered; a new provider registers its own handler and retains its own API policy, job, quotas and persistence logic. This is dispatch orchestration only, NOT a shared availability engine or a 100-provider conditional chain.

The GRS handler checks its own `providers.config.price_refresh.scheduler_enabled` (default false) and provider active/online flags before dispatching the existing `SyncGrsDuePricesJob`. The manual `php artisan grs:sync-prices` remains independent of that scheduler flag. Laravel Scheduler must run separately from Horizon; the manual command's `queued` message only indicates dispatch.

`GrsPriceRefreshScheduleService` uses Provider and AccommodationProviderMap repository contracts and the SSP schedule repository. `GrsAvailabilityPersistenceRepository` owns read-side verification queries. The two GRS price Jobs, the console schedule and `HotelSyncService` have no direct database queries. `HotelSyncService` resolves city mappings once before paginating, and availability hotel/room/rate mapping through repositories. Its calendar writes remain inside `RoomCalendarRepository`.

## Availability and dates

- The GRS worker requests the configured range from Tehran today. Days not present in the response are simply absent; do not synthesize missing nights or reject the entire result.
- Dates returned outside the requested range are normalized and stored under the ACTUAL dates supplied by GRS. Verification searches by exactly those returned dates rather than `check_in <= day < check_out`.
- HTTP success with an empty collection is a valid zero-row refresh. It advances `next_gds_run_at` after successful verification, preventing the same empty hotel from being selected every minute.
- Invalid/unusable individual row data (missing room/rate/day, or malformed day) is skipped with a warning rather than failing valid rows. Actual missing room/rate mappings or missing calendar dimensions after persistence still cause a failure and do not advance the due time.
- `last_gds_success_run_at` records request start after API quota, `last_gds_success_at` a successful HTTP response. Only a completed verified refresh advances `next_gds_run_at` by the SSP row's current `refresh_interval_minutes`; 429 and other failed operations leave it unchanged.
- The provider's existing `availability_rate_limit.max_requests` (max 10) and `window_minutes` apply to BOTH availability calls and any necessary supplementary room/property call. Up to 10 SSP due hotels can be selected per scan; an additional mapping request consumes another API slot. No new queue or request policy was introduced.

## Catalog coordinates

Out-of-range or invalid coordinates from GRS are stored as `null` in nullable accommodation coordinate columns; the remaining hotel is created/mapped normally. No decimal-point guess and no complete-batch failure for bad coordinates.

## Horizon / rollout

`config/horizon.php` already watches `default`, `grs-hotels`, `grs-prices`. The Horizon worker timeout is unchanged at 60 seconds, GRS per-property timeout 55 seconds, Redis `retry_after` defaults to 90 seconds. Add any future named queue to Horizon in the SAME change that introduces that queue. No queue was added in this checkpoint.

Keep automatic GRS scheduling disabled during live debugging. Restart managed Horizon processes to load changes safely. Do not retry failed price jobs blindly: each retry may send provider HTTP requests. Old failed entries can reflect an earlier code version. The assistant did not execute Laravel tests, touch production SSP, or issue real GRS HTTP calls; this change was reviewed through connected repository files only.
