# GRS V2 — SSP-driven price and inventory refresh

Updated: 2026-09-19. Development branch: `feature/reservation-foundation` (fast-forwarded from `main` at `e6e2b9a`). No changes from this checkpoint are merged to `main`.

## Scope and ownership

- `grs:sync-hotels` is the separate weekly catalog fetch; subsequent `grs-hotels` jobs only write local database records.
- `grs:sync-prices` is the GRS-specific pricing command. Its due scan and property workers operate only on GRS availability; future providers must have separate policy/services, not conditionals inside this pricing flow.
- SSP owns `shared_ssp.hotel_price_refresh_schedules` and its schema; no GDS migration changes it.
- `gds_id` identifies OUR `accommodations.id`. `accommodation_provider_maps` resolves it together with GRS provider ID to `provider_property_id`, which is the ONLY property ID sent to GRS HTTP. `hotel_accommodation_id` remains the SSP ID.

## Repository / scheduler boundary

`routes/console.php` only declares the GRS command, cadence, overlap guard and a service-based `schedulerEnabled()` callback. It has no Eloquent/SQL queries. The callback reads the provider through the existing `ProviderRepositoryInterface` / `ProviderRepository`; future large-scale scheduling may aggregate provider eligibility in one repository query without merging provider-specific pricing policies.

`GrsPriceRefreshScheduleService` delegates provider and accommodation-map lookups to their repositories, SSP selection/timestamp writes to `HotelPriceRefreshScheduleRepository`, and persistence verification to `GrsAvailabilityPersistenceRepository`. Neither GRS price Job issues database queries directly. Existing `HotelSyncService` still performs the actual availability persistence through its calendar repository; historical DB queries elsewhere in that service were not refactored as part of this isolated fix.

`providers.config.price_refresh.scheduler_enabled` disables only automatic scheduling; a manual `php artisan grs:sync-prices` still dispatches the due scan. Laravel Scheduler must actually be running separately from Horizon. The command's `queued` message means dispatch, not successful HTTP or calendar writes.

## Date and availability contract

- Request the provider-configured interval (`default_days=90` unless overridden) from Tehran today. Do NOT require that every date in the requested range appears in the API response.
- Valid provider-returned dates outside the request interval are normalized and persisted with their actual date. Verification checks only the date/room/rate dimensions actually returned, using an explicit `whereIn(day, returned days)`; it never rejects a result simply because its date is out of range.
- A successful HTTP response with zero availability rows is a valid empty result: zero calendar writes, no failure, and SSP `next_gds_run_at` advances normally. Rows with unusable room/rate/date fields are skipped with warnings; genuine missing mappings or missing persisted dimensions remain failures.
- Only after the successful response, any required supplementary room mapping, and verification of returned rows does `next_gds_run_at` advance using SSP's current `refresh_interval_minutes`. HTTP errors, 429, missing hotel map or verified persistence failures do NOT advance the due date.
- Existing shared API limiter (`availability_rate_limit.max_requests`, max 10, window configured by provider) counts each availability and supplementary request individually. A scan selects up to capacity due rows in `next_gds_run_at,id` order; there is no claim or artificial backoff.

## Catalog coordinates

Invalid or out-of-range provider latitude/longitude become `null` when inserting a new accommodation. The nullable coordinates do not stop creation/mapping of the hotel. This does not guess the provider's intended decimal placement.

## Horizon / operational status

`config/horizon.php` already includes `default`, `grs-hotels`, `grs-prices`. The Horizon supervisor timeout remains **60 seconds**, the GRS property Job timeout **55 seconds**. Any additional named queue must be configured in Horizon in the same change. No new queue was added here.

Automatic scheduling should remain disabled until an operator has confirmed working scheduler execution and a live safe run. Do not retry all failed jobs indiscriminately: previous failed entries may be historical versions, and retrying availability jobs sends provider requests again. Update/restart managed Horizon workers to load new code. The production API and shared DB are not accessible from the GitHub connector; no Laravel test suite or production HTTP requests were executed here by the assistant.
