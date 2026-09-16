# Availability — child coverage, request size and mixed-room allocation

Branch: `chatgpt/fix-child-policy-coverage` (created from `main` at `f47ac6a3f65093451f6aa61e61590dddeed92473`).

Scope: rate and capacity search only. No reservation flow, booking, or inventory hold was changed.

## Policy fields

- `max_children_covered`: maximum **combined** number of children and infants who can receive a hotel child/infant policy rate. `null` means this shared cap is absent; `0` means no policy rate is available.
- `max_infants_covered`: additional infant-only cap within the shared cap. `null` means there is no infant-specific cap; it does **not** cancel `max_children_covered`.
- If the combined allowance has been exhausted, any further child or infant is calculated as an adult. In this case `infant_when_disabled=as_child` cannot bypass the combined cap.
- If the infant-only cap is exhausted but the combined allowance remains, apply `infant_when_disabled`: `as_child` uses the child policy only when the child age range accepts the passenger; `as_adult` applies adult rules.
- Child ages use the inclusive `max_child_age`; infant ages are strictly below `max_infant_age`. An explicit adult stays adult regardless of age.
- If the allowance is shared between free and half-rate candidates, free comes first so the ordering in the incoming passengers array does not alter that known relative discount. Fixed-price policies are not globally reoptimized by room price in this change.

## Confirmed examples

- Korosh: `max_children_covered=1`, `max_infants_covered=null`. Two adults plus two four-year-olds produce three adult-equivalent passengers and one free infant. A capacity-two room without extra capacity must not be selected.
- Espinas: its existing `max_infants_covered=1` already prevents giving two infants the free rate. A recognized shared-allowance sentence on a future provider sync is stored in `max_children_covered`; any stale infant cap is cleared when provider coverage was explicitly recognized.
- When `max_children_covered=2` and `max_infants_covered=1`, a second infant may use the child tariff through `as_child` if within the child's age range. It cannot be considered a second free infant.

## Sync and data considerations

`HotelChildPolicyTextParser` recognizes sentences that jointly limit free and half-rate children. Explicit numeric limits supplied in provider rule `conditions` are also read. `HotelDataSyncService` writes both coverage fields when the provider explicitly provides at least one recognized coverage limit; when no coverage is supplied it retains manually curated values. Existing rows are not silently bulk-migrated: review hotel policies whose descriptions and limits disagree before production use, especially a shared limit combined with a previously separate infant limit.

The current database schema does not have an independent third field for both a shared cap and a separate child-only cap in addition to the infant-only cap. If that combination becomes a business requirement, it needs a separate field and data migration rather than overloading the two fields.

## Room-count limit

The search request accepts 1 through 5 entries in `rooms`. More than 5 must fail FormRequest validation with HTTP 422 and a `rooms` validation error, before rate/capacity calculation begins. Exactly 5 is valid. Direct service callers also reject counts greater than five.

## Mixed room-type availability and price optimization

- Treat every requested room as an individual assignment, even if passenger counts are identical. Reuse priced room options per identical composition to avoid redundant pricing work, but never require all requests to use one room type.
- For each composition, offer only room types that fit passengers and have an open, priced, available calendar for **every night**, with a consistent provider and rate plan across the stay.
- Sort eligible offers by **full-stay `total_price`**, then room type, provider and rate plan as deterministic tie breakers. Explore low-priced options first and keep the lowest aggregate total for **all** requested rooms; a branch-and-bound check handles different passenger compositions where a simple first-fit greedy choice would be suboptimal.
- Every assignment consumes one unit of the room type's inventory on every night. Across provider/rate-plan offers of the same physical room type, do not consume the same stock twice. Reject the entire hotel if no feasible complete combination exists.
- Return one result per request in original `requested_room_index` order. Set `required_inventory` to the number of rooms actually selected from that room type, even when other room types are part of the same request.
- Reproduction: hotel 35 on 2027-03-04 has four single rooms (room type 3402 at 49,000,000 IRR per room) and two of the cheapest double type (3403 at 70,000,000 IRR). Five individual one-adult requests should choose four singles plus one double, 266,000,000 IRR total for one night, subject to the corresponding room metadata, calendars and availability predicates being valid.

## Changed files

- `app/Services/AvailabilityFilterService.php`: shared/infant-specific allowances and individual cheapest feasible mixed room-type assignment with shared nightly stock.
- `app/Services/HotelChildPolicyTextParser.php`: shared allowance extraction and explicit provider limits.
- `app/Services/HotelDataSyncService.php`: consistency of explicit coverage caps on sync.
- `app/Http/Requests/Front/AvailabilityRequest.php`: five-room limit and validation message.
- `tests/Unit/ChildPolicyCoverageTest.php`: targeted coverage, pricing, capacity, parser cases.
- `tests/Feature/AvailabilityRoomLimitTest.php`: five-room acceptance and six-room rejection checks.
- `tests/Unit/AvailabilityMixedRoomAllocationTest.php`: five-request mixed allocation, two-night stock, constrained/cheap-room conflict and unavailable combination.

## Validation and next steps

The tests are committed but cannot be run against the full Laravel application or your live database through the GitHub connector. Run `php artisan test --filter=ChildPolicyCoverageTest`, `php artisan test --filter=AvailabilityRoomLimitTest`, and `php artisan test --filter=AvailabilityMixedRoomAllocationTest`; then verify hotel 35, the Korosh child-policy example and the six-room 422 response in Postman. The modified PHP source and added PHP test file passed `php -l` syntax checks in an isolated PHP environment, not application test execution. This work intentionally does not adjust check-in/CTA/CTD rules, total-invoice pricing, or reservation flows.
