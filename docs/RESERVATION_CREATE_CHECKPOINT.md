# Reservation Create checkpoint — 2026-09-20

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
    "accommodation_id": 123,
    "rooms": [{
      "room_calendar_id": 5480,
      "price": 25000000,
      "guests": [{
        "type": 1,
        "first_name": "Ali",
        "last_name": "Ahmadi",
        "national_id": "0012345678"
      }]
    }]
  }
}
```

`room_calendar_id` is the existing `nightly_prices[0].calendar_id` from Availability, representing the CHECK-IN night. `price` is the original total price for this room for the FULL stay, in IRR. Neither provider ID, rate plan ID, booker object, acc_code nor sale_amount is accepted from the requester. Calendar identity selects one specific provider and rate plan internally; never substitute a cheaper or different provider during Create.

## Lifecycle

1. For a structurally valid request, persist Reservation + Hotel + Room + Guest in one transaction with status **1** and original room prices. The reservation identifier exposed to callers is `reservations.id`. The legacy `reservation_number` column is retained for compatibility and set to the SAME numeric ID expressed as a string.
2. Commit transaction before contacting any provider. Check ONLY the online/active provider resolved from the selected check-in calendar. Validate all dates from check-in inclusive to check-out exclusive, room/rate-plan mapping, restrictions, availability and aggregate inventory for repeated room selections.
3. Outcome 4: validated online and available. Store validated prices and total. Customer amount = `max(original_total, validated_total)`. `price_change` is true only when validated total exceeds initial total. Lower provider price stays as internal margin; no commission is finalized before purchase.
4. Outcome 3: no capacity/rate for the selected complete stay. Ticket remains persisted and closed.
5. Outcome 2: price check could not be completed (offline/inactive selected provider, incomplete provider mapping, provider error or unsupported pricing). Ticket remains persisted, validation_error is recorded; do not mark ready for payment.

Create does NOT pay, book, issue, call provider reserve/hold, or finalize supplier finance. Codes 5–11 belong to later operations. A malformed JSON request may return HTTP 422 before ticket creation; this is separate from a valid ticket whose supplier validation fails.

## Data migration

Run `php artisan migrate --force` in deployment BEFORE enabling the new Create code. Exactly one additive migration `2026_09_20_120000_prepare_reservation_create_validation.php` alters reservations and reservation_rooms and makes the obsolete reservation_number nullable so the database can generate its ID first. Original create migrations remain untouched and no migrate:fresh is required. Room calendar ID is stored without a foreign key because past calendars are pruned.

## Verification

Run `php artisan test --filter=ReservationCreateValidationTest` and `php artisan route:list --path=api/v1/front/reservations`. Exercise 1-room increase/decrease, zero capacity, offline and supplier timeout against safe fixtures before production rollout. The provider adapter availability response is the live check, not a booking/hold. The tests were committed but cannot be executed using GitHub file-access alone; run on the application host or in your development environment.
