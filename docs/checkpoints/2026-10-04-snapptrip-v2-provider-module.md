# SnappTrip V2 Provider Module — 2026-10-04

## Source contract

- SnappTrip B2B API version: 2.0.
- Integration document reviewed: 1.5.2.
- Swagger is the primary provider contract.
- Base URL: `https://b2bapiv2.snapptrip.com/`.
- Default API-key quota: 120 requests per minute for each API key.
- SnappTrip monetary values are Toman; Domestic Hotel canonical monetary values are IRR.

## Architectural decision

Domestic Hotel remains a modular monolith. Provider integrations are isolated modules under:

`App\Modules\HotelProviders\V2\<Provider>`

The shared Hotel domain remains canonical and provider-neutral. Provider modules own HTTP, authentication, provider rate limiting, provider response mapping, provider-specific orchestration, jobs, commands, and provider-specific persistence helpers.

SnappTrip is implemented only through `App\Modules\HotelProviders\V2\SnappTrip`. There is no legacy `SnappTripAdapter`, no compatibility facade through `ProviderAdapterInterface`, and the SnappTrip provider row keeps `class = null`. Old `token` configuration is removed instead of migrated; only the V2 `api_key` configuration is supported.

GRS/Eghamat24 should be migrated to the same module architecture later without changing its current production behavior during the SnappTrip rollout.

## Provider lifecycle

`providers.is_active` is the provider-integration/outbound switch. `providers.is_online` is a procurement-mode flag and does not disable catalog, rate refresh, availability recheck, balance or cancellation API operations.

When a provider is inactive:

- no provider HTTP requests are allowed;
- provider background sync and refresh jobs do no external work;
- provider commands that require external access refuse to run;
- provider online booking is not attempted;
- provider cancellation polling is not attempted.

Domestic Hotel itself remains available:

- search and availability still answer from locally persisted data;
- missing local rates simply produce no offer for that date;
- reservation creation is not rejected because the supplier is disabled;
- reservation live supplier price recheck is skipped and the reservation continues to the manual procurement flow;
- no provider historical/accounting data is deleted when the provider is disabled.

## Hotel-centric price refresh

The shared SSP hotel schedule selects an accommodation once. The refresh scheduler then fans that hotel out to every active, enabled provider map that can supply it. Provider modules do not independently scan the full hotel schedule during normal scheduled refresh.

Each provider persists its own normalized offer rows. `room_calendars` therefore intentionally keeps all provider prices with the existing canonical uniqueness:

`room_type_id + rate_plan_id + day + provider_id`

No cheapest-only write is performed. Search/availability evaluates complete valid stays and returns the cheapest valid offer from one provider; it never combines different providers night by night. Keeping all provider rows preserves future flexibility for online procurement rules such as success rate, balance, commission, confirmation mode or cancellation terms.

Provider API quota remains provider-specific. SnappTrip allows 120 requests per minute and the scheduled hotel refresh currently performs two calendar requests per hotel (`foreigner=false` and `foreigner=true`), so the SnappTrip scheduled capacity is conservatively 60 hotels per minute. When multiple providers are enabled, the shared scheduler uses the lowest enabled provider hotel capacity so every selected hotel can be fanned out consistently.

There is no provider-specific price-refresh state table. The SSP hotel schedule remains the scheduling source of truth; provider-specific quota/cooldown is kept inside each provider module.

## Static hotel data

SnappTrip hotel details sync persists the data needed by the current hotel domain:

- canonical accommodation identity and location fields;
- hotel facilities through the existing `facilities` and `accommodation_facility` structures, matching the GRS details flow;
- room types and their canonical `capacity` / `extra_capacity` fields;
- rate plans derived from SnappTrip `board_type`;
- child age policy information;
- provider-specific hotel details that do not have canonical accommodation columns.

Gallery and review endpoints are intentionally not called or persisted. Facility icons are intentionally ignored because the canonical facility schema does not use them.

Provider room capacities are not duplicated on `room_type_provider_maps`; SnappTrip adult and extra-bed capacities update the canonical `room_types.capacity` and `room_types.extra_capacity` fields.

SnappTrip has no provider rate-plan identifier. The module therefore creates a stable room-scoped provider rate-plan mapping key (`room:{provider_room_id}:{domestic|foreign}`), while the canonical rate-plan meal semantics remain on `rate_plans`. No generic JSON metadata columns are added to provider mapping tables.

## Canonical foreign-guest rule

Foreign eligibility is represented by `rate_plans.is_foreign_guest`.

No separate foreign price column exists. Price remains scoped by the canonical offer dimension:

`room_type_id + rate_plan_id + day + provider_id`

SnappTrip `foreigner=false` and `foreigner=true` responses therefore persist into different domestic/foreign rate plans. A foreign guest cannot reserve a rate plan with `is_foreign_guest = false`.

## Passenger pricing

`room_calendars` has provider-neutral explicit passenger price fields:

- `child_daily_rate`
- `infant_daily_rate`

Rules:

- non-null explicit provider price is authoritative, including zero;
- null means the provider did not quote that passenger type;
- only null falls back to `HotelChildPolicy` pricing;
- SnappTrip currently documents `child_price`; the infant column is ready for providers that supply an explicit infant amount.

## Currency boundary

No SnappTrip Toman amount may escape the SnappTrip module.

- inbound SnappTrip money: Toman -> IRR before persistence or return to the Hotel domain;
- outbound monetary filters: IRR -> Toman inside the SnappTrip module only;
- generic Hotel/Reservation services never contain provider-code currency conditionals.

## Availability and rack packages

SnappTrip calendar prices are indicative. Live reservation recheck uses SnappTrip hotel availability for the definitive stay base price when outbound access is enabled.

SnappTrip rack/package windows are stored as provider-neutral `provider_stay_packages`; they are not stored in `room_calendars.rack_rate` because a SnappTrip rack is a stay-window constraint rather than a price column.

Rack rows are historical data. A current provider refresh marks overlapping old package rows inactive and marks packages observed in the latest response active with `last_seen_at`; it does not delete stale packages. Only active packages constrain new availability/reservation requests. Stay validation itself is centralized in the shared `ProviderStayPackageRepository`; SnappTrip does not keep a duplicate validator.

## Purchase

Domestic Hotel continues to use the manual procurement flow for SnappTrip by default.

Provider config:

`purchase.online_enabled = false`

The SnappTrip module nevertheless implements create/lock/confirm/status API operations so they can be enabled later without redesigning the integration. Confirmation always follows the documented mandatory booking-status inquiry.

## Cancellation

SnappTrip cancellation support is implemented in the provider module and persisted in `provider_cancellations`:

- room cancellation rules;
- create cancellation;
- inquiry;
- accept only from `CALCULATED`;
- reject only from `CALCULATED`;
- polling of `REQUESTED`, `ACCEPTED`, and `DONE` states until a final state is observed.

This support is not wired as a generic automatic cancellation flow for other providers.

## Provider configuration defaults

Fresh SnappTrip provider configuration uses:

- `api_key = api_key_snapptrip`
- `rate_limit.max_requests = 120`
- `rate_limit.window_minutes = 1`
- static catalog sync enabled;
- price refresh scheduler enabled;
- online purchase disabled.

The placeholder key is rejected for actual outbound calls. The real API key must be written only to the production database/provider configuration and must never be committed to source control.

## Queues

- `snapptrip-static`
- `snapptrip-prices`
- `snapptrip-operations`

## Commands

- `php artisan snapptrip:health`
- `php artisan snapptrip:sync-catalog`
- `php artisan snapptrip:sync-details`
- `php artisan snapptrip:sync-prices`
- `php artisan snapptrip:sync-balance`
- `php artisan snapptrip:sync-cancellations`

All provider-outbound commands honor the provider outbound guard.

## Database changes

Schema changes are intentionally split by responsibility instead of bundling unrelated models into one migration:

- `2026_10_04_143500_add_explicit_guest_rates_to_room_calendars.php`
- `2026_10_04_143510_create_accommodation_provider_details_table.php`
- `2026_10_04_143520_create_provider_stay_packages_table.php`
- `2026_10_04_143530_create_provider_cancellations_table.php`

No provider-specific price refresh state table is introduced. No SnappTrip gallery/review tables, facility icon column, duplicated provider room capacity columns, or provider-metadata JSON columns are introduced.

Every newly added migration column has an English database comment.
