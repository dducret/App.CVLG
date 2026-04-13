<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['export_type'] ?? 'members';
    $filename = 'cvlg-' . $type . '-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    if ($type === 'members') {
        fputcsv($out, ['firstName', 'lastName', 'email', 'type', 'role']);
        foreach (fetch_all('SELECT Person.firstName, Person.lastName, Person.email, Member.type, Person.role FROM Member INNER JOIN Person ON Person.id = Member.person ORDER BY Person.lastName') as $row) {
            fputcsv($out, $row);
        }
    } elseif ($type === 'dues') {
        fputcsv($out, ['member', 'year', 'status', 'amount', 'paymentMethod']);
        foreach (fetch_all('SELECT Person.firstName || " " || Person.lastName AS member, YearFee.year, MemberYearFee.status, MemberYearFee.amount, MemberYearFee.paymentMethod FROM MemberYearFee INNER JOIN Member ON Member.id = MemberYearFee.member INNER JOIN Person ON Person.id = Member.person INNER JOIN YearFee ON YearFee.id = MemberYearFee.yearFee ORDER BY YearFee.year DESC') as $row) {
            fputcsv($out, $row);
        }
    } elseif ($type === 'bookings') {
        fputcsv($out, ['journey', 'date', 'member', 'status', 'guestName']);
        foreach (fetch_all('SELECT Journey.Label, Journey.dateFrom, Person.firstName || " " || Person.lastName AS member, Booking.status, Booking.guestName FROM Booking INNER JOIN Journey ON Journey.id = Booking.journey INNER JOIN Member ON Member.id = Booking.member INNER JOIN Person ON Person.id = Member.person ORDER BY Journey.dateFrom DESC') as $row) {
            fputcsv($out, $row);
        }
    } else {
        fputcsv($out, ['person', 'quantity', 'price', 'date']);
        foreach (fetch_all('SELECT Person.firstName || " " || Person.lastName AS person, Ticket.quantity, Ticket.price, Ticket.date FROM Ticket INNER JOIN Person ON Person.id = Ticket.person ORDER BY Ticket.date DESC') as $row) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit;
}

render_header('Exports CSV', $user);
?>
<div class="row">
    <div class="col s12 l6">
        <div class="soft-box">
            <form method="post">
                <div class="input-field">
                    <select name="export_type">
                        <option value="members">Membres</option>
                        <option value="dues">Cotisations</option>
                        <option value="bookings">Reservations</option>
                        <option value="payments">Tickets / paiements</option>
                    </select>
                    <label>Type d'export</label>
                </div>
                <button class="btn" type="submit">Generer le CSV</button>
            </form>
        </div>
    </div>
</div>
<?php render_footer(); ?>
