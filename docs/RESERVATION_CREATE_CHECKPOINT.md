# Reservation Create checkpoint — 2026-09-20 (corrected contract)

Branch: `feature/reservation-foundation`

## Endpoint and request

`POST /api/v1/front/reservations/create`

```json
{
  "agency_id": 1,
  "check_in": "2027-03-04",
  "check_out": "2027-03-05",
  "first_name": "Afshin",
  "last_name": "Efati",
  "mobile": "09120000000",
  "email": "test@example.com",
  "hotel": {
    "rooms": [{
      "room_calendar_id": 5480,
      "price": 25000000,
      "guests": [{
        "type": 1,
        "first_name": "Ali",
        "last_name": "Ahmadi",
        "country_id": 1,
        "national_id": "0012345678"
      }]
    }]
  }
}
```

The example `country_id` must be replaced with the ID of Iran in the deployed `countries` table (`iso2=IR`); never assume Iran's numeric ID is 1. For a non-Iranian guest (`iso2` other than `IR`), send `passport_number` instead of `national_id`; existing passport issuer/expiry validation continues to apply. `country_id` (nationality) is **required for each guest**, including infants and children.

`room_calendar_id` is the existing `nightly_prices[0].calendar_id` from Availability, representing the CHECK-IN night. `price` is the original total price for this room for the FULL stay, in IRR. The request does **not** need accommodation ID, room type ID, rate plan ID or provider ID. These all come from the selected calendar inside GDS. Neither a separate offer token, booker object, acc_code nor sale_amount is accepted from the requester. Never substitute a cheaper or different provider during Create.

## Lifecycle

1. For a structurally valid request, persist Reservation + Hotel + Room + Guest in one transaction with status **1** and original room prices. The reservation identifier exposed to callers is `reservations.id`; the legacy `reservation_number` column remains for integrations and mirrors that ID as a string.
2. Resolve the accommodation/room type/rate plan/provider from the selected calendars. All rooms must belong to the same accommodation and each supplied calendar must represent the check-in day. If calendars have been deleted or a selection mixes hotels, still persist the full ticket and guest snapshot, then close with **status 3**; no provider API call is made. When every selected calendar is gone, the ticket's `reservation_hotels.accommodation_id` remains NULL instead of inventing a hotel.
3. Commit the initial transaction before contacting any provider. Check ONLY the online/active provider resolved from the selected calendar. Validate all dates from check-in inclusive to check-out exclusive, mappings, restrictions, availability and aggregate inventory for repeated room selections.
4. Outcome **4**: validated online and available. Store validated room prices and total. Customer amount = `max(original_total, validated_total)`. `price_change` is true only when validated total exceeds initial total. Lower provider price remains an internal margin; no commission is finalized before purchase.
5. Outcome **3**: selected calendar missing, mixed hotel selection, no capacity or no rate for the full stay. Ticket remains persisted and closed.
6. Outcome **2**: price check could not be completed (offline/inactive selected provider, incomplete provider mapping, provider error or unsupported pricing). Ticket remains persisted with `validation_error`; do not mark ready for payment.

Create does NOT pay, book, issue, call provider reserve/hold, or finalize supplier finance. Codes 5–11 belong to later operations. A malformed request (including missing nationality or identity document) returns HTTP 422 before ticket creation; this is distinct from a structurally valid ticket whose supplier validation fails.

## Production migrations

Run `php artisan migrate --force` in deployment **before enabling the new Create code**. Original create migrations are unchanged; `migrate:fresh` is neither required nor safe on the deployed database.

1. `2026_09_20_120000_prepare_reservation_create_validation.php` adds initial/validated amounts and room calendar/provider snapshots and allows legacy `reservation_number` to be written after database-generated ID.
2. `2026_09_20_130000_allow_unresolved_reservation_hotel.php` makes only `reservation_hotels.accommodation_id` nullable, so the ticket, selected calendar IDs and guests can all be persisted when the chosen calendar disappeared. No new request IDs or separate offer table are added.

## Verification

Run `php artisan test --filter=ReservationCreateRequestTest` and `php artisan test --filter=ReservationCreateValidationTest`, plus `php artisan route:list --path=api/v1/front/reservations`. Test an Iran nationality ID with national ID, non-Iran nationality ID with passport/issuer/expiry, missing nationality, unavailable calendar and mixed-hotel selections, 1-room increase/decrease, zero capacity, offline and supplier timeout. The tests are committed but cannot be executed through GitHub file access; run on the development/application environment. Do not deploy without checking the additive migrations on a database copy.
