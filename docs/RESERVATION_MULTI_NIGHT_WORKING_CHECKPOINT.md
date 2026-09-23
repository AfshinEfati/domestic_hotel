# Reservation Multi Night Working Checkpoint

## Change
Reservation create flow is being moved from single room calendar selection to multi-night room calendar snapshots.

## Target request structure
- Reservation total price snapshot
- Room expected total price
- Room calendar list containing calendar_id, date and expected_price

## Planned persistence change
- ReservationRoom keeps room level snapshot data.
- ReservationRoomCalendar stores each stay night calendar snapshot.

## Planned implementation areas
- Reservation request validation
- Reservation service create flow
- Reservation room persistence
- Reservation room calendar relation
