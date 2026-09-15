# Domestic Hotel GDS - Database Architecture

**Branch:** `feature/reservation-foundation`  
**Snapshot date:** 2026-09-15  
**Purpose:** management review of the implemented business schema and relationships.

## Executive model

The business schema is grouped into six domains: Location, Provider Integration, Hotel Catalog, Availability & Pricing, Reservation, and Procurement & Payment.

## Reservation & procurement ERD

```mermaid
erDiagram
    accommodations ||--}o room_types : "accommodation_id"
    accommodations ||--}o rate_plans : "accommodation_id"
    reservations ||--}o reservation_hotels : "reservation_id"
    accommodations ||--}o reservation_hotels : "accommodation_id"
    reservation_hotels ||--}o reservation_rooms : "reservation_hotel_id"
    room_types o|--}o reservation_rooms : "room_type_id"
    rate_plans o|--}o reservation_rooms : "rate_plan_id"
    reservation_rooms ||--}o reservation_guests : "reservation_room_id"
    countries o|--}o reservation_guests : "country_id"
    providers o|--}o purchase_manual_rules : "provider_id"
    accommodations o|--}o purchase_manual_rules : "accommodation_id"
    reservation_hotels ||--}o reservation_purchases : "reservation_hotel_id"
    providers ||--}o reservation_purchases : "provider_id"
    providers o|--}o reservation_purchases : "quoted_provider_id"
    purchase_manual_rules o|--}o reservation_purchases : "manual_rule_id"
    reservation_purchases ||--}o reservation_purchase_segments : "reservation_purchase_id"
    reservation_rooms ||--}o reservation_purchase_segments : "reservation_room_id"
    reservation_purchases ||--}o reservation_purchase_payments : "reservation_purchase_id"
    reservation_purchases ||--o| reservation_manual_purchases : "reservation_purchase_id"
    reservation_purchases ||--}o reservation_provider_operations : "reservation_purchase_id"
```

## Catalog & provider ERD

```mermaid
erDiagram
    countries ||--}o states : "country_id"
    countries ||--}o cities : "country_id"
    states o|--}o cities : "state_id"
    cities ||--}o provider_city_maps : "city_id"
    providers ||--}o provider_city_maps : "provider_id"
    cities ||--}o accommodations : "city_id"
    accommodation_types ||--}o accommodations : "accommodation_type_id"
    accommodations ||--}o accommodation_provider_maps : "accommodation_id"
    providers ||--}o accommodation_provider_maps : "provider_id"
    accommodations ||--}o room_types : "accommodation_id"
    room_type_names o|--}o room_types : "room_type_name_id"
    room_types ||--}o room_type_provider_maps : "room_type_id"
    providers ||--}o room_type_provider_maps : "provider_id"
    accommodations ||--}o rate_plans : "accommodation_id"
    rate_plans ||--}o rate_plan_provider_maps : "rate_plan_id"
    providers ||--}o rate_plan_provider_maps : "provider_id"
    facility_groups ||--}o facilities : "facility_group_id"
    accommodations ||--}o accommodation_facility : "accommodation_id"
    facilities ||--}o accommodation_facility : "facility_id"
    accommodations ||--}o rules : "hotel_id"
    rule_categories o|--}o rules : "rule_category_id"
    accommodations ||--o| hotel_child_policy : "accommodation_id"
    providers ||--o| provider_credit_balances : "provider_id"
```

## Availability & pricing ERD

```mermaid
erDiagram
    accommodations ||--}o room_types : "accommodation_id"
    accommodations ||--}o rate_plans : "accommodation_id"
    accommodations ||--}o room_calendars : "accommodation_id"
    room_types ||--}o room_calendars : "room_type_id"
    rate_plans ||--}o room_calendars : "rate_plan_id"
    providers ||--}o room_calendars : "provider_id"
    room_calendars ||--}o room_calendar_snapshots : "room_calendar_id"
    providers ||--}o room_calendar_snapshots : "provider_id"
    providers ||--o| provider_pricing_rules : "provider_id"
```

## Open decisions before schema approval

1. `PROJECT_CONTEXT.md` specifies an internal GDS reservation reference, but the current `reservations` migration has no reference column.
2. `PROJECT_CONTEXT.md` specifies `quantity` for purchase segments, but the current `reservation_purchase_segments` migration has no `quantity` column.
3. `is_final` exists on hotel and room candidates, but uniqueness of a single final candidate is not enforced by a dedicated database constraint.
4. Some legacy catalog migrations still use database ENUM columns (`rate_plans`, `hotel_child_policy`).

## Deliverable sources

- `docs/database/domestic_hotel_schema.dbml` - editable ERD source for dbdiagram.io-compatible tools.
- This file - GitHub-native Mermaid view for reviews.
