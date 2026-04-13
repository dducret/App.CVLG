<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();
if (!can_manage_journeys($user)) {
    flash('error', 'Vous ne pouvez pas gerer les remontees.');
    redirect('dashboard.php');
}

$pdo = db();

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM Journey WHERE id = ?')->execute([(int) $_GET['delete']]);
    flash('success', 'Remontee supprimee.');
    redirect('journeys.php');
}
if (isset($_GET['start'])) {
    $pdo->prepare('UPDATE Journey SET started = 1 WHERE id = ?')->execute([(int) $_GET['start']]);
    flash('success', 'Remontee demarree.');
    redirect('journeys.php?view=' . (int) $_GET['start']);
}
if (isset($_GET['end'])) {
    $pdo->prepare('UPDATE Journey SET ended = 1 WHERE id = ?')->execute([(int) $_GET['end']]);
    flash('success', 'Remontee terminee.');
    redirect('journeys.php?view=' . (int) $_GET['end']);
}
if (isset($_GET['validate_booking'])) {
    $pdo->prepare("UPDATE Booking SET status = 'validated', validatedAt = ? WHERE id = ?")
        ->execute([now_iso(), (int) $_GET['validate_booking']]);
    flash('success', 'Reservation validee.');
    redirect('journeys.php?view=' . (int) ($_GET['view'] ?? 0));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_journey') {
    $payload = [
        (int) ($_POST['driver'] ?? 0),
        (int) ($_POST['vehicule'] ?? 0),
        trim($_POST['label'] ?? ''),
        trim($_POST['kind'] ?? 'club'),
        trim($_POST['dateFrom'] ?? ''),
        trim($_POST['dateTo'] ?? ''),
        trim($_POST['timeStart'] ?? ''),
        trim($_POST['timeEnd'] ?? ''),
        (int) $user['id'],
        trim($_POST['notes'] ?? ''),
    ];
    $id = (int) ($_POST['journey_id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('UPDATE Journey SET driver=?, vehicule=?, Label=?, kind=?, dateFrom=?, dateTo=?, timeStart=?, timeEnd=?, createdBy=?, notes=? WHERE id=?')
            ->execute([...$payload, $id]);
        flash('success', 'Remontee mise a jour.');
    } else {
        $pdo->prepare('INSERT INTO Journey(driver, vehicule, Label, kind, dateFrom, dateTo, timeStart, timeEnd, createdBy, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute($payload);
        flash('success', 'Remontee creee.');
    }
    redirect('journeys.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_booking') {
    $journeyId = (int) ($_POST['journey_id'] ?? 0);
    $memberId = (int) ($_POST['member_id'] ?? 0);
    $guestName = trim($_POST['guest_name'] ?? '');
    $status = journey_reserved_count($journeyId) >= journey_capacity($journeyId) ? 'waitlist' : 'booked';
    $pdo->prepare('INSERT INTO Booking(journey, member, status, guestName, qrCode) VALUES (?, ?, ?, ?, ?)')
        ->execute([$journeyId, $memberId, $status, $guestName ?: null, strtoupper(bin2hex(random_bytes(4)))]);
    flash('success', $status === 'waitlist' ? 'Reservation ajoutee en attente.' : 'Reservation ajoutee.');
    redirect('journeys.php?view=' . $journeyId);
}

$drivers = fetch_all('SELECT Driver.id, Person.firstName || " " || Person.lastName AS name FROM Driver INNER JOIN Person ON Person.id = Driver.person ORDER BY Person.lastName, Person.firstName');
$vehicles = fetch_all('SELECT id, name FROM Vehicule ORDER BY name');
$members = fetch_all('SELECT Member.id, Person.firstName || " " || Person.lastName AS name FROM Member INNER JOIN Person ON Person.id = Member.person ORDER BY Person.lastName, Person.firstName');
$journeys = fetch_all('SELECT Journey.*, Vehicule.name AS vehicleName, Person.firstName || " " || Person.lastName AS driverName FROM Journey LEFT JOIN Vehicule ON Vehicule.id = Journey.vehicule LEFT JOIN Driver ON Driver.id = Journey.driver LEFT JOIN Person ON Person.id = Driver.person ORDER BY Journey.dateFrom DESC, Journey.timeStart DESC');
$edit = isset($_GET['edit']) ? fetch_one('SELECT * FROM Journey WHERE id = ?', [(int) $_GET['edit']]) : null;
$view = isset($_GET['view']) ? fetch_one('SELECT Journey.*, Vehicule.name AS vehicleName, Vehicule.seats, Person.firstName || " " || Person.lastName AS driverName FROM Journey LEFT JOIN Vehicule ON Vehicule.id = Journey.vehicule LEFT JOIN Driver ON Driver.id = Journey.driver LEFT JOIN Person ON Person.id = Driver.person WHERE Journey.id = ?', [(int) $_GET['view']]) : null;
$bookings = $view ? fetch_all('SELECT Booking.*, Person.firstName, Person.lastName FROM Booking INNER JOIN Member ON Member.id = Booking.member INNER JOIN Person ON Person.id = Member.person WHERE Booking.journey = ? ORDER BY Booking.createdAt', [$view['id']]) : [];

render_header('Gestion des remontees', $user);
?>
<div class="row">
    <div class="col s12 l7">
        <div class="soft-box">
            <h5>Liste des remontees</h5>
            <table class="striped">
                <thead><tr><th>Libelle</th><th>Date</th><th>Heure</th><th>Chauffeur</th><th>Vehicule</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($journeys as $journey): ?>
                    <tr>
                        <td><?= e($journey['Label']) ?></td>
                        <td><?= e(format_date($journey['dateFrom'])) ?></td>
                        <td><?= e($journey['timeStart']) ?></td>
                        <td><?= e($journey['driverName'] ?: '-') ?></td>
                        <td><?= e($journey['vehicleName'] ?: '-') ?></td>
                        <td class="right-align">
                            <a class="btn-small blue" href="?view=<?= (int) $journey['id'] ?>">Voir</a>
                            <a class="btn-small" href="?edit=<?= (int) $journey['id'] ?>">Editer</a>
                            <a class="btn-small red" href="?delete=<?= (int) $journey['id'] ?>" onclick="return confirm('Supprimer cette remontee ?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col s12 l5">
        <div class="soft-box">
            <h5><?= $edit ? 'Modifier une remontee' : 'Creer une remontee' ?></h5>
            <form method="post">
                <input type="hidden" name="action" value="save_journey">
                <input type="hidden" name="journey_id" value="<?= e($edit['id'] ?? '') ?>">
                <div class="input-field"><input type="text" id="label" name="label" value="<?= e($edit['Label'] ?? '') ?>" required><label for="label" class="active">Libelle</label></div>
                <div class="input-field"><input type="date" id="dateFrom" name="dateFrom" value="<?= e($edit['dateFrom'] ?? today_iso()) ?>"><label for="dateFrom" class="active">Date</label></div>
                <div class="input-field"><input type="date" id="dateTo" name="dateTo" value="<?= e($edit['dateTo'] ?? today_iso()) ?>"><label for="dateTo" class="active">Date fin</label></div>
                <div class="input-field"><input type="time" id="timeStart" name="timeStart" value="<?= e($edit['timeStart'] ?? '09:00') ?>"><label for="timeStart" class="active">Heure debut</label></div>
                <div class="input-field"><input type="time" id="timeEnd" name="timeEnd" value="<?= e($edit['timeEnd'] ?? '10:00') ?>"><label for="timeEnd" class="active">Heure fin</label></div>
                <div class="input-field">
                    <select name="driver">
                        <?php foreach ($drivers as $driver): ?>
                            <option value="<?= (int) $driver['id'] ?>" <?= (int) ($edit['driver'] ?? 0) === (int) $driver['id'] ? 'selected' : '' ?>><?= e($driver['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Chauffeur</label>
                </div>
                <div class="input-field">
                    <select name="vehicule">
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?= (int) $vehicle['id'] ?>" <?= (int) ($edit['vehicule'] ?? 0) === (int) $vehicle['id'] ? 'selected' : '' ?>><?= e($vehicle['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Vehicule</label>
                </div>
                <div class="input-field">
                    <?php $kind = $edit['kind'] ?? 'club'; ?>
                    <select name="kind">
                        <?php foreach (['club', 'membres', 'ecole'] as $value): ?>
                            <option value="<?= e($value) ?>" <?= $kind === $value ? 'selected' : '' ?>><?= e($value) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Type</label>
                </div>
                <div class="input-field"><textarea id="notes" name="notes" class="materialize-textarea"><?= e($edit['notes'] ?? '') ?></textarea><label for="notes" class="active">Notes</label></div>
                <button class="btn" type="submit">Enregistrer</button>
            </form>
        </div>
    </div>
</div>

<?php if ($view): ?>
    <div class="row">
        <div class="col s12">
            <div class="soft-box">
                <h5>Detail de la remontee: <?= e($view['Label']) ?></h5>
                <p>
                    <?= e(format_date($view['dateFrom'])) ?> - <?= e($view['timeStart']) ?> / <?= e($view['timeEnd']) ?>
                    <span class="pill"><?= e($view['driverName'] ?: '-') ?></span>
                    <span class="pill"><?= e($view['vehicleName'] ?: '-') ?> (<?= e((string) ($view['seats'] ?? 0)) ?> places)</span>
                    <span class="pill"><?= journey_reserved_count((int) $view['id']) ?>/<?= journey_capacity((int) $view['id']) ?> confirmees</span>
                </p>
                <p>
                    <?php if (!(int) $view['started']): ?><a class="btn green" href="?start=<?= (int) $view['id'] ?>">Demarrer</a><?php endif; ?>
                    <?php if (!(int) $view['ended']): ?><a class="btn orange" href="?end=<?= (int) $view['id'] ?>">Terminer</a><?php endif; ?>
                </p>
                <div class="row">
                    <div class="col s12 l7">
                        <table class="striped">
                            <thead><tr><th>Participant</th><th>Statut</th><th>Invite</th><th>QR</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= e($booking['firstName'] . ' ' . $booking['lastName']) ?></td>
                                    <td><?= e($booking['status']) ?></td>
                                    <td><?= e($booking['guestName'] ?: '-') ?></td>
                                    <td><code><?= e($booking['qrCode']) ?></code></td>
                                    <td><?php if ($booking['status'] !== 'validated'): ?><a class="btn-small" href="?view=<?= (int) $view['id'] ?>&validate_booking=<?= (int) $booking['id'] ?>">Valider</a><?php endif; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col s12 l5">
                        <h6>Ajouter une reservation</h6>
                        <form method="post">
                            <input type="hidden" name="action" value="add_booking">
                            <input type="hidden" name="journey_id" value="<?= (int) $view['id'] ?>">
                            <div class="input-field">
                                <select name="member_id">
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?= (int) $member['id'] ?>"><?= e($member['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Membre</label>
                            </div>
                            <div class="input-field"><input type="text" id="guest_name" name="guest_name"><label for="guest_name">Invite (optionnel)</label></div>
                            <button class="btn" type="submit">Ajouter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php render_footer(); ?>
