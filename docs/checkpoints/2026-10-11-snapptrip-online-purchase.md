# SnappTrip V2 Online Purchase — 2026-10-11

## Goal

Complete provider-side online procurement without blocking the caller that submits the paid reservation for purchase.

`POST /api/v1/front/reservations/{reservation_id}/purchase` remains the acceptance boundary. It decides manual vs online procurement, persists the purchase plan, returns `book_requested`, and delegates provider I/O to the queue.

## SnappTrip contract used

Primary source is the supplied SnappTrip Swagger, with the supplied integration PDF used for workflow notes.

Booking sequence:

1. `POST /booking/create`
2. optional `POST /booking/{code}/lock` when the contract permits/needs explicit lock
3. `POST /booking/{code}/confirm`
4. mandatory `GET /booking/{code}` status check after confirm

The create request carries `checkin`, `checkout`, `hotel_id`, `phone`, optional `email`, and `rooms`. Each room contains `room_id`, `extra_beds`, `children`, `infants`, and guests. SnappTrip requires all room IDs in one create request to be the same, so an online provider purchase is split by provider room type when necessary.

The provider response price is normalized from Toman to IRR inside the SnappTrip module.

## Async boundary

The front/whitelist request never waits for SnappTrip create/confirm/status calls. Online purchases are queued on `snapptrip-operations` after the database transaction commits.

The queue job is unique per `reservation_purchase_id`, preventing parallel execution of the same purchase.

## Idempotency and retries

SnappTrip does not document an idempotency key for `/booking/create`.

Therefore:

- once create returns a `reservation_code`, that code is persisted before lock/confirm;
- subsequent attempts resume the same booking by status instead of creating another booking;
- rate-limit failures are safely released/retried;
- connection/5xx failures after a reservation code exists are retried against the existing booking;
- an ambiguous create failure before a reservation code is received is **not** automatically re-created, because the provider may have created a booking even though the response was lost; the purchase moves to `under_review`.

## Price safety

The create response `price` is the SnappTrip payable amount. If it is greater than `reservation_purchases.provider_quoted_amount`, the system does not auto-confirm. The purchase moves to `under_review` with `provider_status=price_increased`.

If a booking is already confirmed and the final provider price is greater than the quote, the issued purchase is stored as `payment_required` instead of hiding the financial difference.

## Booking states

- success: `confirmed`
- confirmable: `draft`, `payment_on`
- pending/poll: `availability_checking`, `availability_pending`, `availability_confirmed`
- terminal failure: `failed`, `payment_failed`, `expired`, `availability_failed`, `availability_rejected`, `overbook`
- unknown states move to `under_review` instead of being guessed

`issue_failed (7)` is not used for automatic provider failure; that status remains the explicit panel rejection state.

## Recovery actions

Admin endpoints:

- `POST /api/v1/admin/reservations/{reservation_id}/purchases/{reservation_purchase}/online/retry`
- `POST /api/v1/admin/reservations/{reservation_id}/purchases/{reservation_purchase}/online/to-manual`

Retry resumes an existing non-terminal booking code when possible. Terminal failures and price-increase retries clear provider booking fields and start a new provider booking.

Convert-to-manual changes only the selected purchase to offline/manual and creates the existing `reservation_manual_purchases` row. It does not require the operator to re-create the reservation.

## Provider capability boundary

Online procurement is registered through `HotelProviderRegistry` using a provider-neutral `OnlinePurchaseHandler` contract. SnappTrip supplies the first V2 implementation. Providers that resolve as online but do not yet have an executable handler are routed to the existing manual workflow instead of leaving a purchase stuck as online.

This is also the extension point intended for GRS/Eghamat24 after its provider module migration.

## Configuration

Safe defaults remain:

```text
purchase.online_enabled = false
purchase.lock_enabled = false
purchase.status_poll_seconds = 15
purchase.status_poll_attempts = 20
```

Online purchase must be explicitly enabled in the SnappTrip provider database configuration after booking-flow testing. Explicit lock remains disabled unless the SnappTrip contract confirms lock permission.

## Database

No schema migration is required for this checkpoint. Existing fields are used:

- `reservation_purchases.confirmation_code`
- `reservation_purchases.provider_status`
- `reservation_purchases.purchase_amount`
- `reservation_purchases.purchase_mode`
- `reservation_purchases.manual_reason`
- `reservation_purchases.issued_at`
- existing `provider_requests` provider HTTP diagnostics

`PurchaseManualReason::ONLINE_FAILED = 7` is an application-level integer code only.
