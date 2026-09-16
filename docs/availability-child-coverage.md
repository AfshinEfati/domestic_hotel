# Availability — child coverage and request-size checkpoint

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

The search request accepts 1 through 5 entries in `rooms`. More than 5 must fail FormRequest validation with HTTP 422 and a `rooms` validation error, before rate/capacity calculation begins. Exactly 5 is valid.

## Changed files

- `app/Services/AvailabilityFilterService.php`: shared and infant-specific allowances, age classification, and stable relative-discount priority.
- `app/Services/HotelChildPolicyTextParser.php`: shared allowance extraction and explicit provider limits.
- `app/Services/HotelDataSyncService.php`: consistency of explicit coverage caps on sync.
- `app/Http/Requests/Front/AvailabilityRequest.php`: explicit five-room limit and human-readable validation message.
- `tests/Unit/ChildPolicyCoverageTest.php`: targeted coverage, pricing, capacity, parser cases.
- `tests/Feature/AvailabilityRoomLimitTest.php`: five-room acceptance and six-room rejection checks.

## Validation and next steps

These tests were added to source control. Connector access alone does not run local PHP/PHPUnit or your live database. Run `php artisan test --filter=ChildPolicyCoverageTest` and `php artisan test --filter=AvailabilityRoomLimitTest`, then verify the Korosh two-adults/two-four-year-olds request and the six-room 422 response in Postman. This work intentionally does not adjust check-in/CTA/CTD rules, total-invoice pricing, or reservation flows.
