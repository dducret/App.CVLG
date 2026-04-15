# Admin User Manual

## Audience

This guide is for administrators managing the full application scope.

## Main Navigation

- `Dashboard`
- `Members`
- `Dues`
- `Journeys`
- `Drivers`
- `Vehicles`
- `Managers`
- `Configuration`
- `Stripe payments`
- `Exports`
- `Communication`

## Dashboard

Use the dashboard to:

- Review key counts across the system
- Review recent journal entries
- Review the next upcoming shuttles

## Members

Use `Members` to:

- Search members by name or nickname
- Filter by member type
- Create a member
- Edit personal, contact, role, and address data
- Set username and password
- Set the preferred language
- Set the member type
- Enable or disable booking permission
- Add a license entry
- Delete a member

Important:

- Deleting a member removes both the member record and the linked person record.

## Dues

Use `Dues` to:

- Select the working year
- Generate yearly dues for all members
- Set fee values by member type during generation
- Review status, amount, payment method, and payment date
- Mark an unpaid due as collected

Important:

- Member self-service Stripe payments can also settle dues automatically.

## Journeys

Administrators have the same journey management capabilities as logistics users:

- Create, edit, delete, start, and end journeys
- Review bookings on a journey
- Validate bookings
- Add operational bookings manually

## Drivers

Use `Drivers` to manage:

- Which members are registered as drivers
- Driver operational status

## Vehicles

Use `Vehicles` to manage:

- Vehicle identity
- Registration
- Label
- Seat capacity
- Operational status

## Managers

Use `Managers` to:

- Register a member as manager
- Set manager rights
- Edit or remove manager entries

Current rights values:

- None
- Read
- Modify
- Full

## Configuration

`Configuration` controls general settings, booking rules, SMTP settings, and Stripe settings.

General settings currently include:

- Club name
- Contact email
- Ticket price
- Active membership fee
- Supporter membership fee
- Booking window in days

SMTP settings currently include:

- SMTP host
- SMTP port
- SMTP username
- SMTP password
- From email
- From name
- Reply-To

Stripe settings currently include:

- Public application base URL
- Stripe publishable key
- Stripe secret key
- Stripe currency
- A switch to enable or disable member Stripe checkout

Booking rules currently include:

- Blocking two bookings at the same time
- Maximum confirmed bookings per day
- Allowing waitlist after the daily confirmed limit
- Maximum waitlist size per journey
- Maximum waitlist entries per member per day

Important:

- Update configuration carefully because these values directly affect booking decisions.
- Stripe checkout stays disabled until the required keys and public base URL are set.
- SMTP sending stays unavailable until the required SMTP settings are complete.

## Stripe Payments

Use `Stripe payments` to:

- Review whether Stripe is currently enabled
- Review the public base URL and configured currency
- Review the local payment journal
- Review recent remote Stripe Checkout sessions
- Review recent Stripe balance transactions

## Communication

Use `Communication` to:

- Compose a message
- Target all members, drivers, managers, active members, or supporters
- Add extra email recipients manually
- Upload attachments
- Save a draft
- Send the email through SMTP
- Review the message list, status, recipient count, and send timestamp

Current behavior:

- Messages are stored and tracked inside the application.
- Sending uses the configured SMTP account with STARTTLS authentication.
- Drafts can be edited or deleted before sending.
- SMTP delivery errors are shown in the interface and stored on the message.

## Exports

Use `Exports` to generate CSV files for:

- Members
- Dues
- Bookings
- Ticket records

## Role And Access Notes

- Admin-only screens in the current build include `Configuration`, `Managers`, `Communication`, and `Exports`.
- Members, dues, journeys, drivers, and vehicles are split across roles and should be delegated accordingly in operations.
- The admin-like navigation replaces the member navigation in the current UI.

## Practical Notes

- Keep member role codes and booking permission aligned with real club responsibilities.
- Review booking rules after any policy change.
- Export data before major operational changes when an audit snapshot is useful.
