<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();
$upcoming = fetch_all(
    'SELECT Booking.*, Journey.Label, Journey.dateFrom, Journey.timeStart
     FROM Booking
     INNER JOIN Journey ON Journey.id = Booking.journey
     WHERE Booking.member = ? AND Journey.dateFrom >= date("now") AND Booking.disable = 0
     ORDER BY Journey.dateFrom, Journey.timeStart',
    [(int) $user['memberId']]
);
$past = fetch_all(
    'SELECT Booking.*, Journey.Label, Journey.dateFrom, Journey.timeStart
     FROM Booking
     INNER JOIN Journey ON Journey.id = Booking.journey
     WHERE Booking.member = ? AND Journey.dateFrom < date("now") AND Booking.disable = 0
     ORDER BY Journey.dateFrom DESC, Journey.timeStart DESC
     LIMIT 20',
    [(int) $user['memberId']]
);

render_header('Mon historique', $user);
?>
<div class="row">
    <div class="col s12 l6">
        <div class="soft-box">
            <h5>Reservations a venir</h5>
            <ul class="collection">
                <?php foreach ($upcoming as $booking): ?>
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
    <div class="col s12 l6">
        <div class="soft-box">
            <h5>Reservations passees</h5>
            <ul class="collection">
                <?php foreach ($past as $booking): ?>
                    <li class="collection-item">
                        <strong><?= e($booking['Label']) ?></strong><br>
                        <?= e(format_date($booking['dateFrom'])) ?> - <?= e($booking['timeStart']) ?><br>
                        <span class="pill"><?= e($booking['status']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (can_manage_journeys($user)): ?>
                <p><a class="btn" href="journeys.php">Acceder a la creation de remontees</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php render_footer(); ?>
