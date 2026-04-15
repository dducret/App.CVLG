# CVLG User Manuals

This folder contains the maintainable user manuals for the current PHP application.

## Source Of Truth

- The English manuals in `Documentation/manuals/en/` are the master versions.
- The French manuals in `Documentation/manuals/fr/` must keep the same structure and section order.
- When a feature changes, update the English file first, then update the matching French file in the same commit when possible.

## Role Guides

- Member guide: booking, waitlist rules, Stripe payments for dues and tickets, profile, and history.
- Logistics guide: shuttle operations, drivers, vehicles, members, dues, and operational bookings.
- Admin guide: configuration, Stripe and SMTP setup, members, dues, communications, exports, and access control.

## File Map

- `Documentation/manuals/en/member.md`
- `Documentation/manuals/en/logistics.md`
- `Documentation/manuals/en/admin.md`
- `Documentation/manuals/fr/membre.md`
- `Documentation/manuals/fr/logistique.md`
- `Documentation/manuals/fr/admin.md`

## Writing Rules

- Keep section titles aligned across English and French.
- Document actual behavior in the current build, not planned features.
- Prefer short task-oriented steps over long explanations.
- Call out role restrictions and important side effects explicitly.
- Keep navigation lists aligned with `app/layout.php`, because admin-like users do not see the member navigation set.
