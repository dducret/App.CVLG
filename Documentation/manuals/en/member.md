# Member User Manual

## Audience

This guide is for standard members using the member-facing area of CVLG.

## Main Navigation

- `My profile`: update personal details, language, address, and password.
- `My dues`: review yearly dues and mark a due as paid in the current build.
- `My tickets`: buy ticket packs and review ticket purchases.
- `Bookings`: reserve or cancel upcoming shuttle seats.
- `My history`: review upcoming and past bookings.

## Sign In

1. Open the login page.
2. Select your language if needed.
3. Sign in with your email or username and password.
4. After login, members are redirected to `Bookings`.

## My Profile

Use `My profile` to maintain:

- First name, last name, nickname, mobile number
- Birthday, gender, nationality
- Street, street number, postal code, city, country
- Preferred language
- Password

The page also shows:

- Your login email
- Your role code
- Your member type
- Your licenses and their validity dates

## My Dues

Use `My dues` to:

- View yearly dues by year
- See amount, status, payment method, and payment date
- Use the `Pay` action on unpaid dues

Current behavior:

- The `Pay` button marks the due as paid with a simulated payment method.

## My Tickets

Use `My tickets` to:

- See the unit ticket price
- See your remaining available rides
- Buy a new ticket pack by quantity
- Review past ticket purchases

Important:

- Bookings depend on available remaining rides.

## Bookings

The `Bookings` page is the main member operational screen.

You can:

- View available shuttles for today and the next two days
- Open a booking confirmation modal by clicking a time slot
- Add an optional guest name for a tandem passenger
- Review your current bookings
- Cancel one of your future bookings
- Read the active booking rules displayed on screen

The page also shows:

- Remaining free seats per shuttle
- Driver and vehicle when assigned
- Your own booking state: reserved or waitlist

## Booking Rules

The current build can enforce these rules:

- A member cannot book the same shuttle twice.
- A member can be blocked from booking two shuttles at the same date and time.
- Confirmed bookings can be limited per day.
- Additional requests on the same day can move to the waitlist instead of being confirmed.
- Waitlist size can be limited per shuttle.
- Waitlist entries can also be limited per member per day.

## When A Booking Is Refused

The booking request is refused when one of these conditions applies:

- Your member account is not allowed to book.
- You have no remaining rides.
- The shuttle is no longer open for booking.
- You already booked that shuttle.
- A rule blocks the request.

## My History

Use `My history` to:

- Review upcoming bookings with status and QR code
- Review recent past bookings

## Practical Notes

- If your account is not regularized, the booking page displays a warning and booking is disabled.
- If you run out of rides, buy tickets before attempting another booking.
- Language changes made in `My profile` affect your next screens after save.
