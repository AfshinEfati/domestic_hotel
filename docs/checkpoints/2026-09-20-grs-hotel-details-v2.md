# Checkpoint — Domestic Hotel / GRS V2 — 2026-09-20

## Project and status
- Repository: `AfshinEfati/domestic_hotel`
- Working branch: `feature/reservation-foundation`.
- Latest implementation commit before this checkpoint: `c8ba90dd0c2cd0669b8f649db55f796d291c5097`.
- This checkpoint only records the conversation and implemented scope. It is not a claim of deployment or successful execution.

## User decisions — do not expand scope without approval
1. Maintain separate workflows for GRS (`اقامت۲۴`) hotel catalog, full hotel details, and price/inventory. Do not couple them back together.
2. Hotel-type mapping: only the 16 canonical `accommodation_types` records; unknown provider types map to one shared `نامشخص` record. No automatic creation of other hotel types. User will handle deletion of previously duplicated data. Do not create cleanup/report/migration work without explicit permission.
3. The new full-details synchronization is GRS-only, under V2, not an always-on price-refresh operation. Read one mapped GRS property at a time, max 10 HTTP requests per minute, for all mapped hotels, once weekly on Friday. Expected full run is approximately four hours, not measured.
4. Full-details sync needs to populate/update local room types and provider room mappings, rate plans and provider rate mappings, hotel rules, child policy, and facilities / facility groups / hotel relations based on the property-detail response. Match on the correct hotel using existing provider mapping; do not overwrite another hotel's records. Price/inventory remain in their own flow.
5. Do not silently add migrations, reports, cleanup tools, data operations, CI/CD changes or unrelated refactors. Ask first for work outside agreed scope.

## Evidence from user-provided API examples
- Property details example: `value.property.id=5` (Tara), includes hotel `facilities`, `room_types` with nested `rate_plans`, property-level `rate_plans`, `rules`, category `children`, and all associated descriptions and conditions. Example room ID 97 and rate-plan IDs 273, 3935, 5013. It is a *different hotel* from the availability example.
- Availability example: `value.rooms` for `property_id=2065`, with room IDs 422875, 423102, 422873, 414307, 423103; each has rate plan ID 2104 and day-by-day prices/inventory. Availability does not carry full property-level rules, facilities or full room details.
- Therefore the rate/inventory response cannot replace the full property-details endpoint, and details from property 5 must never be applied to property 2065.

## What was wrong before the new details command
- Original V2 catalog `ProcessGrsHotelBatchJob`: creates/maps hotels and attaches only pre-existing facility records found in catalog data, not rooms, rate plans or rules.
- `GRSAdapter::fetchAvailability()` flattens room/rate plan data to daily rows and identifiers; does not preserve full metadata.
- `RateLimitedGrsAdapter::fetchRoomTypes()` explicitly removed `property_rules` and `property_facilities`; this caused missing rules/child-policy/facilities in the rate-refresh side path.
- `HotelSyncService` fetched supplemental room details only when room/rate maps were missing; existing maps did not imply full metadata was refreshed.
- Existing rate/inventory persistence checks room/rate maps and corresponding local records for non-empty rows; a successful empty response could still indicate zero persisted rows. Empty `rooms` output for a hotel with verified nonzero prices requires checking the specific internal accommodation ID and API read path; do not claim a confirmed root cause without that evidence.

## Implemented new independent V2 workflow
- New Artisan command: `php artisan grs:sync-details` (`app/Console/Commands/V2/SyncGrsHotelDetailsCommand.php`). Dispatches only details jobs for mapped GRS hotels with a six-second interval; not the catalog or price jobs.
- New client: `app/Domain/Hotel/V2/GrsHotelDetailsClient.php`; fetches and validates full property response and its property ID.
- New job: `app/Jobs/Hotel/V2/SyncGrsHotelDetailsJob.php`; queue `grs-details`, independent limiter with max 10 detail requests/60 s and a Redis lock to avoid catch-up bursts; does not use the availability quota or `RateLimitedGrsAdapter`.
- New persistence repository: `app/Domain/Hotel/Repositories/GrsHotelDetailsRepository.php`; persists full mapped property details using room/rate mappings, rules, child-policy parser, and facilities. Confirm implementation code for specific edge cases before making assertions about production behavior.
- `routes/console.php`: weekly Friday 06:00 `Asia/Tehran`, `withoutOverlapping()`, separately from catalog sync (`grs:sync-hotels` Friday 05:00) and price refresh. Note: scheduled command queues work, so overlapping protection on the command alone does not prove the previous week's queued work has finished.
- `config/horizon.php`: separate `grs-details` queue and dedicated Supervisor; retains existing main supervisor for `default`, `grs-hotels`, `grs-prices`.
- Focused test added: `tests/Feature/GrsHotelDetailsV2Test.php` (command dispatch/queue spacing, client full response, mismatch ID rejection). Test **added but not executed**; no CI status established.
- Implementation commit before checkpoint: `c8ba90dd0c2cd0669b8f649db55f796d291c5097` (10 commits after `4f2938175417530883d8fd2816d838037ec3d3a6` at last comparison).

## Known boundaries / next steps require user's instructions
- Current `room_types` table does not have `accept_child`, and `rate_plans` lacks `nationality` and `board_type`; these fields exist in provider response. No migration was added; ask the user before expanding schema.
- No server deployment, production DB operation, actual 4-hour run, or test execution has been performed or confirmed in this chat. A new Horizon Supervisor requires deploying updated code/config and restarting long-lived Horizon workers when user proceeds with deployment.
- Do not alter the independent price-refresh workflow, supplier API quotas, provider schedules or legacy workflow without explicit authorization.
- Prior hotel type work was committed at `4f2938175417530883d8fd2816d838037ec3d3a6`; 16 canonical IDs plus `نامشخص` only. User said they would remove the previously created extra type rows themselves.

## Resume prompt
`پروژه domestic_hotel رو از docs/checkpoints/2026-09-20-grs-hotel-details-v2.md روی branch feature/reservation-foundation ادامه بده. اول آخرین تغییرات و وضعیت تست/استقرار رو چک کن؛ بدون دستور جدید من چیزی را تغییر نده.`
