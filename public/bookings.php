<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();
if (empty($user['memberId'])) {
    flash('error', 'Aucun profil membre actif.');
    redirect('dashboard.php');
}

if (isset($_GET['book'])) {
    if (!member_can_book($user)) {
        flash('error', 'Reservation impossible: cotisation impayee ou role non autorise.');
        redirect('bookings.php');
    }
    if (remaining_tickets((int) $user['id']) <= 0) {
        flash('error', 'Aucun ticket disponible.');
        redirect('tickets.php');
    }

    $journeyId = (int) $_GET['book'];
    $alreadyBooked = (int) fetch_value(
        "SELECT COUNT(*) FROM Booking WHERE journey = ? AND member = ? AND disable = 0 AND status IN ('booked', 'validated', 'waitlist')",
        [$journeyId, (int) $user['memberId']]
    );
    if ($alreadyBooked > 0) {
        flash('error', 'Vous avez deja reserve cette remontee.');
        redirect('bookings.php');
    }

    $status = journey_reserved_count($journeyId) >= journey_capacity($journeyId) ? 'waitlist' : 'booked';
    db()->prepare('INSERT INTO Booking(journey, member, status, guestName, qrCode) VALUES (?, ?, ?, ?, ?)')
        ->execute([$journeyId, (int) $user['memberId'], $status, trim($_GET['guest'] ?? '') ?: null, strtoupper(bin2hex(random_bytes(4)))]);
    flash('success', $status === 'waitlist' ? 'Reservation ajoutee en liste d attente.' : 'Reservation confirmee.');
    redirect('bookings.php');
}

if (isset($_GET['cancel'])) {
    db()->prepare("UPDATE Booking SET disable = 1, status = 'cancelled' WHERE id = ? AND member = ?")
        ->execute([(int) $_GET['cancel'], (int) $user['memberId']]);
    flash('success', 'Reservation annulee.');
    redirect('bookings.php');
}

$window = (int) setting('booking_window_days', '3');
$journeys = fetch_all(
    'SELECT Journey.*, Vehicule.name AS vehicleName, Person.firstName || " " || Person.lastName AS driverName
     FROM Journey
     LEFT JOIN Vehicule ON Vehicule.id = Journey.vehicule
     LEFT JOIN Driver ON Driver.id = Journey.driver
     LEFT JOIN Person ON Person.id = Driver.person
     WHERE Journey.dateFrom BETWEEN date("now") AND date("now", ?)
     ORDER BY Journey.dateFrom, Journey.timeStart',
    ['+' . $window . ' day']
);
$myBookings = fetch_all(
    "SELECT Booking.*, Journey.Label, Journey.dateFrom, Journey.timeStart
     FROM Booking
     INNER JOIN Journey ON Journey.id = Booking.journey
     WHERE Booking.member = ? AND Booking.disable = 0 AND Booking.status IN ('booked', 'validated', 'waitlist')
     ORDER BY Journey.dateFrom, Journey.timeStart",
    [(int) $user['memberId']]
);
$bookingByJourney = [];
foreach ($myBookings as $booking) {
    $bookingByJourney[$booking['journey']] = $booking;
}

render_header('Reservations', $user);
?>
<div class="row">
    <div class="col s12 l8">
        <div class="soft-box">
            <h5>Remontees reservables</h5>
            <p>Tickets restants: <strong><?= remaining_tickets((int) $user['id']) ?></strong></p>
            <?php if (!member_can_book($user)): ?>
                <div class="card-panel amber lighten-4">La reservation est desactivee pour votre compte tant que votre situation membre n'est pas reguliere.</div>
            <?php endif; ?>
            <div class="row">
                <?php foreach ($journeys as $journey): ?>
                    <?php $mine = $bookingByJourney[$journey['id']] ?? null; ?>
                    <div class="col s12 m6">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title"><?= e($journey['Label']) ?></span>
                                <p><?= e(format_date($journey['dateFrom'])) ?> - <?= e($journey['timeStart']) ?></p>
                                <p>Chauffeur: <?= e($journey['driverName'] ?: '-') ?></p>
                                <p>Vehicule: <?= e($journey['vehicleName'] ?: '-') ?></p>
                                <p>Places confirmees: <?= journey_reserved_count((int) $journey['id']) ?>/<?= journey_capacity((int) $journey['id']) ?></p>
                            </div>
                            <div class="card-action">
                                <?php if ($mine): ?>
                                    <span class="pill"><?= e($mine['status']) ?></span>
                                    <a href="?cancel=<?= (int) $mine['id'] ?>">Annuler</a>
                                <?php elseif (member_can_book($user)): ?>
                                    <a href="?book=<?= (int) $journey['id'] ?>">Reserver</a>
                                    <a href="?book=<?= (int) $journey['id'] ?>&guest=Invite">Reserver avec invite</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col s12 l4">
        <div class="soft-box">
            <h5>Reservations en cours</h5>
            <ul class="collection">
                <?php foreach ($myBookings as $booking): ?>
                    <li class="collection-item">
                        <strong><?= e($booking['Label']) ?></strong><br>
                        <?= e(format_date($booking['dateFrom'])) ?> - <?= e($booking['timeStart']) ?><br>
                        <span class="pill"><?= e($booking['status']) ?></span>
                        <code><?= e($booking['qrCode']) ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php render_footer(); ?>
