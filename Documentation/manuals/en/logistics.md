# Logistics User Manual

## Audience

This guide is for logistics users who manage shuttle operations. In the current build, this usually means users with access to shuttle management and operational resources.

## Main Navigation

- `Dashboard`: summary metrics and recent journal entries.
- `Members`: search, review, create, and update members.
- `Dues`: collect dues and generate yearly dues.
- `Journeys`: create and operate shuttles.
- `Drivers`: maintain the driver list and driver status.
- `Vehicles`: maintain vehicle records and seat capacity.
- `My profile`, `Bookings`, and `My history`: personal user tools remain available.

## Dashboard

The dashboard provides:

- High-level counts
- Recent journal items

Current behavior:

- Non-admin users without shuttle management rights are redirected away from the dashboard.

## Journeys

`Journeys` is the main logistics screen.

You can:

- Create a journey with label, dates, times, driver, vehicle, type, and notes
- Edit an existing journey
- Delete a journey
- Open a journey detail view
- Start a journey
- End a journey
- Review all bookings on a journey
- Validate a booking
- Add a booking manually for a member
- Add an optional guest for a booking

The journey detail view also shows:

- Driver name
- Vehicle name and seat count
- Confirmed seat usage versus capacity
- QR code per booking

## Drivers

Use `Drivers` to:

- Promote a member to driver
- Set the driver status
- Edit an existing driver record
- Remove a driver record

Current driver status labels:

- Available
- Resting
- Out of service

## Vehicles

Use `Vehicles` to:

- Create a vehicle
- Update registration, label, seat count, and status
- Remove a vehicle

Seat count matters because booking capacity is derived from the assigned vehicle.

Current vehicle status labels:

- Operational
- Under maintenance
- Broken down

## Members

Logistics users can access the member management screen.

They can:

- Search by name or nickname
- Filter by member type
- Edit member details
- Create a member
- Delete a member
- Enable or disable booking permission
- Add a license while editing

## Dues

Logistics users can access the dues management screen.

They can:

- Review dues for a selected year
- Mark an unpaid due as collected
- Generate yearly dues for all member types

Current behavior:

- Collected dues are marked with a cash payment method on this screen.

## Operational Booking Notes

- Manual bookings created from `Journeys` use the same booking rule engine as member self-booking.
- A booking can become `waitlist` if capacity or daily booking rules require it.
- Validating a booking changes its status to `validated`.

## Practical Notes

- Assign the correct vehicle before operations, because seat count drives booking capacity.
- Review waitlist status before validating attendance.
- Ending a journey closes its operational lifecycle in the current build.
