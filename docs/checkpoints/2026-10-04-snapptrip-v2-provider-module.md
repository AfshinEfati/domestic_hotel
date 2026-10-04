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

SnappTrip is the first provider implemented with this structure. GRS/Eghamat24 should be migrated to the same module architecture later without changing its current production behavior during the SnappTrip rollout.

## Provider lifecycle

Provider state controls outbound supplier operations only.

When a provider is inactive or offline:

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

Rack rows are historical data. A current provider refresh marks overlapping old package rows inactive and marks packages observed in the latest response active with `last_seen_at`; it does not delete stale packages. Only active packages constrain new availability/reservation requests.

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

Migration `2026_10_04_143500_add_hotel_provider_module_support_schema.php` adds explicit passenger rates and provider module support tables.

Migration `2026_10_04_143510_add_provider_metadata_to_hotel_provider_maps.php` adds provider metadata to room/rate-plan mappings.

Every newly added migration column has an English database comment.
