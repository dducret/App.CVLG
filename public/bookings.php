<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();
if (empty($user['memberId'])) {
    flash('error', 'Aucun profil membre actif.');
    redirect('dashboard.php');
}

$bookingWindow = 2;
$remainingTickets = remaining_tickets((int) $user['id']);
$canRequestBooking = member_can_book($user) && $remainingTickets > 0;
$dailyConfirmedLimit = setting_int('booking_rule_daily_confirmed_limit', 1);
$allowWaitlistAfterDailyLimit = setting_bool('booking_rule_allow_waitlist_after_daily_limit', true);
$journeyWaitlistLimit = setting_int('booking_rule_journey_waitlist_limit', 3);
$dailyWaitlistLimit = setting_int('booking_rule_daily_waitlist_limit', 3);
$sameTimeBlocked = setting_bool('booking_rule_same_time_block', true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_booking') {
    $journeyId = (int) ($_POST['journey_id'] ?? 0);
    $guestName = trim($_POST['guest_name'] ?? '');

    if (!$canRequestBooking) {
        flash('error', 'Reservation impossible: cotisation non reglee ou aucune montee disponible.');
        redirect('bookings.php');
    }

    $journey = fetch_one(
        'SELECT id, Label, dateFrom, timeStart
         FROM Journey
         WHERE id = ?
           AND dateFrom BETWEEN date("now") AND date("now", ?)
           AND ended = 0',
        [$journeyId, '+' . $bookingWindow . ' day']
    );
    if (!$journey) {
        flash('error', 'Cette navette n est plus reservable.');
        redirect('bookings.php');
    }

    $alreadyBooked = (int) fetch_value(
        "SELECT COUNT(*)
         FROM Booking
         WHERE journey = ?
           AND member = ?
           AND disable = 0
           AND status IN ('booked', 'validated', 'waitlist')",
        [$journeyId, (int) $user['memberId']]
    );
    if ($alreadyBooked > 0) {
        flash('error', 'Vous avez deja reserve cette navette.');
        redirect('bookings.php');
    }

    $decision = evaluate_booking_request((int) $user['memberId'], $journeyId, $guestName);
    if (!$decision['allowed']) {
        flash('error', $decision['message']);
        redirect('bookings.php');
    }

    $status = $decision['status'];
    db()->prepare('INSERT INTO Booking(journey, member, status, guestName, qrCode) VALUES (?, ?, ?, ?, ?)')
        ->execute([$journeyId, (int) $user['memberId'], $status, $guestName ?: null, strtoupper(bin2hex(random_bytes(4)))]);
    flash('success', $decision['message']);
    redirect('bookings.php');
}

if (isset($_GET['cancel'])) {
    db()->prepare("UPDATE Booking SET disable = 1, status = 'cancelled' WHERE id = ? AND member = ?")
        ->execute([(int) $_GET['cancel'], (int) $user['memberId']]);
    flash('success', 'Reservation annulee.');
    redirect('bookings.php');
}

$journeys = fetch_all(
    'SELECT Journey.id, Journey.Label, Journey.dateFrom, Journey.timeStart, Journey.timeEnd, Vehicule.name AS vehicleName,
            Person.firstName || " " || Person.lastName AS driverName
     FROM Journey
     LEFT JOIN Vehicule ON Vehicule.id = Journey.vehicule
     LEFT JOIN Driver ON Driver.id = Journey.driver
     LEFT JOIN Person ON Person.id = Driver.person
     WHERE Journey.dateFrom BETWEEN date("now") AND date("now", ?)
       AND Journey.ended = 0
     ORDER BY Journey.dateFrom, Journey.timeStart, Journey.id',
    ['+' . $bookingWindow . ' day']
);
$myBookings = fetch_all(
    "SELECT Booking.id, Booking.journey, Booking.status, Booking.guestName, Booking.qrCode,
            Journey.Label, Journey.dateFrom, Journey.timeStart
     FROM Booking
     INNER JOIN Journey ON Journey.id = Booking.journey
     WHERE Booking.member = ?
       AND Booking.disable = 0
       AND Booking.status IN ('booked', 'validated', 'waitlist')
       AND Journey.dateFrom >= date('now')
     ORDER BY Journey.dateFrom, Journey.timeStart",
    [(int) $user['memberId']]
);

$bookingByJourney = [];
foreach ($myBookings as $booking) {
    $bookingByJourney[$booking['journey']] = $booking;
}

$days = [];
for ($offset = 0; $offset <= $bookingWindow; $offset++) {
    $date = date('Y-m-d', strtotime('+' . $offset . ' day'));
    $days[$date] = [
        'title' => $offset === 0 ? 'Jour' : 'Jour + ' . $offset,
        'date' => $date,
        'slots' => [],
    ];
}

foreach ($journeys as $journey) {
    $date = $journey['dateFrom'];
    if (!isset($days[$date])) {
        continue;
    }

    $journey['reservedSeats'] = journey_reserved_count((int) $journey['id']);
    $journey['capacity'] = journey_capacity((int) $journey['id']);
    $journey['remainingSeats'] = max(0, $journey['capacity'] - $journey['reservedSeats']);
    $journey['myBooking'] = $bookingByJourney[$journey['id']] ?? null;
    $journey['stateLabel'] = $journey['myBooking']
        ? ($journey['myBooking']['status'] === 'waitlist' ? 'Liste d attente' : 'Reserve')
        : ($journey['remainingSeats'] > 0 ? 'Disponible' : 'Complet');
    $journey['stateClass'] = $journey['myBooking']
        ? ($journey['myBooking']['status'] === 'waitlist' ? 'status-waitlist' : 'status-reserved')
        : ($journey['remainingSeats'] > 0 ? 'status-available' : 'status-full');
    $days[$date]['slots'][] = $journey;
}

render_header('Reservations', $user);
?>
<style>
    .booking-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
    .booking-toolbar p { margin: 0; }
    .booking-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; }
    .booking-day { background: linear-gradient(180deg, #ffffff, #f6f9fd); border-radius: 18px; padding: 22px; box-shadow: 0 16px 36px rgba(18, 39, 68, 0.08); min-height: 100%; }
    .booking-day h5 { margin-top: 0; margin-bottom: 6px; }
    .booking-date { color: #607287; margin-bottom: 18px; }
    .booking-slot { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 14px 16px; border-radius: 12px; background: #edf4fb; margin-bottom: 12px; }
    .booking-slot:last-child { margin-bottom: 0; }
    .booking-slot-button { background: transparent; border: 0; padding: 0; text-align: left; cursor: pointer; color: inherit; font: inherit; }
    .booking-slot-button strong { font-size: 1.1rem; color: #16324f; }
    .booking-slot-button:disabled { cursor: not-allowed; opacity: 0.55; }
    .booking-seat { color: #1b5e20; font-weight: 600; white-space: nowrap; }
    .booking-empty { border: 1px dashed #c7d3e0; border-radius: 12px; padding: 18px; color: #607287; background: #fbfcfe; }
    .booking-help { color: #607287; font-size: 0.95rem; margin-top: 18px; margin-bottom: 0; }
    .modal .helper-text-inline { color: #607287; font-size: 0.92rem; }
    .booking-side-list { margin-top: 24px; }
    .booking-side-list .collection-item { line-height: 1.5; }
    .booking-meta { color: #607287; font-size: 0.92rem; }
    .booking-status { margin-top: 8px; }
    .status-badge { display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 700; }
    .status-past { background: #eceff1; color: #455a64; }
    .status-full { background: #ffebee; color: #b71c1c; }
    .status-available { background: #e3f2fd; color: #0d47a1; }
    .status-reserved { background: #e8f5e9; color: #1b5e20; }
    .status-waitlist { background: #fff3e0; color: #e65100; }
    @media (max-width: 992px) {
        .booking-toolbar { flex-direction: column; align-items: flex-start; }
        .booking-grid { grid-template-columns: 1fr; }
    }
</style>
<div class="soft-box">
    <div class="booking-toolbar">
        <p><strong>Il vous reste <?= e((string) $remainingTickets) ?> montée(s) disponible(s)</strong></p>
        <a class="btn" href="tickets.php">Acheter des tickets</a>
    </div>
    <p style="margin-top: 0;">Consultez les navettes disponibles sur les trois prochains jours.</p>
    <div class="booking-grid">
        <?php foreach ($days as $day): ?>
            <section class="booking-day">
                <h5><?= e($day['title']) ?></h5>
                <p class="booking-date"><?= e(format_date($day['date'])) ?></p>
                <?php if ($day['slots'] === []): ?>
                    <div class="booking-empty">Aucune navette pour cette date.</div>
                <?php else: ?>
                    <?php foreach ($day['slots'] as $slot): ?>
                        <div class="booking-slot">
                            <div>
                                <?php if ($slot['myBooking']): ?>
                                    <strong><?= e(str_replace(':', 'h', substr($slot['timeStart'], 0, 5))) ?></strong><br>
                                    <span>Navette reservee</span>
                                <?php else: ?>
                                    <button
                                        type="button"
                                        class="booking-slot-button modal-trigger"
                                        data-target="booking-confirmation-modal"
                                        data-journey-id="<?= (int) $slot['id'] ?>"
                                        data-booking-date="<?= e(format_date($day['date'])) ?>"
                                        data-booking-time="<?= e(str_replace(':', 'h', substr($slot['timeStart'], 0, 5))) ?>"
                                        data-booking-label="<?= e($slot['Label']) ?>"
                                        <?= $canRequestBooking ? '' : 'disabled' ?>
                                    >
                                        <strong><?= e(str_replace(':', 'h', substr($slot['timeStart'], 0, 5))) ?></strong><br>
                                        <span><?= e($slot['Label']) ?></span>
                                    </button>
                                <?php endif; ?>
                                <div class="booking-meta">
                                    Chauffeur: <?= e($slot['driverName'] ?: '-') ?> | Vehicule: <?= e($slot['vehicleName'] ?: '-') ?>
                                </div>
                                <div class="booking-status">
                                    <span class="status-badge <?= e($slot['stateClass']) ?>"><?= e($slot['stateLabel']) ?></span>
                                </div>
                            </div>
                            <span class="booking-seat"><?= e((string) $slot['remainingSeats']) ?> place(s) libre(s)</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
    <p class="booking-help">
        Cliquez sur l'heure d'une navette pour ouvrir la confirmation de reservation<?= $canRequestBooking ? '' : ' (indisponible tant que vous ne pouvez pas reserver)' ?>.
    </p>
</div>
<?php if (!member_can_book($user)): ?>
    <div class="card-panel amber lighten-4">
        La reservation est desactivee pour votre compte tant que votre situation membre n'est pas reguliere.
    </div>
<?php endif; ?>
<?php if ($remainingTickets <= 0): ?>
    <div class="card-panel amber lighten-4">
        Vous n'avez plus de montee disponible. Achetez des tickets pour pouvoir reserver.
    </div>
<?php endif; ?>
<div class="soft-box booking-side-list">
    <h5>Regles de reservation en vigueur</h5>
    <ul class="browser-default" style="margin-top: 0; padding-left: 20px;">
        <?php if ($sameTimeBlocked): ?>
            <li>Deux reservations a la meme heure sont refusees.</li>
        <?php else: ?>
            <li>Les reservations simultanees a la meme heure sont autorisees.</li>
        <?php endif; ?>
        <li>Maximum <?= e((string) $dailyConfirmedLimit) ?> reservation(s) ferme(s) par jour.</li>
        <li>
            <?= $allowWaitlistAfterDailyLimit
                ? 'Au-dela, une autre demande le meme jour passe en liste d attente.'
                : 'Au-dela, aucune autre reservation n est acceptee le meme jour.' ?>
        </li>
        <li>Maximum <?= e((string) $journeyWaitlistLimit) ?> membre(s) en liste d attente par navette.</li>
        <li>Maximum <?= e((string) $dailyWaitlistLimit) ?> navette(s) en liste d attente par jour et par membre.</li>
    </ul>
</div>
<div class="soft-box booking-side-list">
    <h5>Reservations en cours</h5>
    <ul class="collection">
        <?php if ($myBookings === []): ?>
            <li class="collection-item">Aucune reservation en cours.</li>
        <?php else: ?>
            <?php foreach ($myBookings as $booking): ?>
                <?php
                $isPastBooking = strtotime((string) $booking['dateFrom']) < strtotime(date('Y-m-d'));
                $bookingStatusLabel = $isPastBooking
                    ? 'Passee'
                    : ($booking['status'] === 'waitlist' ? 'Liste d attente' : 'Reserve');
                $bookingStatusClass = $isPastBooking
                    ? 'status-past'
                    : ($booking['status'] === 'waitlist' ? 'status-waitlist' : 'status-reserved');
                ?>
                <li class="collection-item">
                    <strong><?= e($booking['Label']) ?></strong><br>
                    <?= e(format_date($booking['dateFrom'])) ?> - <?= e(str_replace(':', 'h', substr($booking['timeStart'], 0, 5))) ?><br>
                    <span class="status-badge <?= e($bookingStatusClass) ?>"><?= e($bookingStatusLabel) ?></span>
                    <?php if (!empty($booking['guestName'])): ?>
                        <span class="pill">Invite: <?= e($booking['guestName']) ?></span>
                    <?php endif; ?>
                    <code><?= e($booking['qrCode']) ?></code><br>
                    <a href="?cancel=<?= (int) $booking['id'] ?>" onclick="return confirm('Annuler cette reservation ?')">Annuler</a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>
<div id="booking-confirmation-modal" class="modal">
    <div class="modal-content">
        <h5>Confirmer la reservation</h5>
        <p>
            <strong id="modal-booking-label">-</strong><br>
            Vous allez reserver la navette du <strong id="modal-booking-date">-</strong> a
            <strong id="modal-booking-time">-</strong>.
        </p>
        <form id="booking-confirmation-form" method="post">
            <input type="hidden" name="action" value="confirm_booking">
            <input type="hidden" name="journey_id" id="modal-journey-id" value="">
            <p>
                <label>
                    <input type="checkbox" id="booking-with-guest">
                    <span>Ajouter un invite / passager biplace</span>
                </label>
            </p>
            <div id="booking-guest-fields" style="display: none;">
                <div class="input-field">
                    <input id="booking-guest-name" name="guest_name" type="text">
                    <label for="booking-guest-name">Nom de l'invite</label>
                </div>
                <p class="helper-text-inline">Utilisez ce champ si vous souhaitez embarquer un passager biplace.</p>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close btn-flat">Annuler</a>
        <button type="submit" form="booking-confirmation-form" id="booking-confirm-button" class="btn">Confirmer la reservation</button>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('booking-confirmation-modal');
    var modalJourneyId = document.getElementById('modal-journey-id');
    var modalLabel = document.getElementById('modal-booking-label');
    var modalDate = document.getElementById('modal-booking-date');
    var modalTime = document.getElementById('modal-booking-time');
    var guestToggle = document.getElementById('booking-with-guest');
    var guestFields = document.getElementById('booking-guest-fields');
    var guestName = document.getElementById('booking-guest-name');

    document.querySelectorAll('.booking-slot-button').forEach(function (button) {
        button.addEventListener('click', function () {
            modalJourneyId.value = button.getAttribute('data-journey-id') || '';
            modalLabel.textContent = button.getAttribute('data-booking-label') || '-';
            modalDate.textContent = button.getAttribute('data-booking-date') || '';
            modalTime.textContent = button.getAttribute('data-booking-time') || '';
            guestToggle.checked = false;
            guestFields.style.display = 'none';
            guestName.value = '';
        });
    });

    if (guestToggle) {
        guestToggle.addEventListener('change', function () {
            guestFields.style.display = guestToggle.checked ? 'block' : 'none';
            if (!guestToggle.checked) {
                guestName.value = '';
            }
        });
    }
});
</script>
<?php render_footer(); ?>
