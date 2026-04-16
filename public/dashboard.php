<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();
if (!is_admin_like($user) && !can_manage_journeys($user)) {
    redirect('bookings.php');
}

$stats = [
    'Membres' => (int) fetch_value('SELECT COUNT(*) FROM Member'),
    'Remontées à venir' => (int) fetch_value("SELECT COUNT(*) FROM Journey WHERE dateFrom >= date('now') AND ended = 0"),
    'Cotisations impayées' => (int) fetch_value("SELECT COUNT(*) FROM MemberYearFee WHERE status != 'paid'"),
    'Tickets vendus' => (int) fetch_value('SELECT COALESCE(SUM(quantity), 0) FROM Ticket'),
];

if (($user['role'] ?? '') === 'L') {
    unset($stats['Cotisations impayées']);
}

$nextJourneys = fetch_all(
    'SELECT Journey.id, Journey.Label, Journey.dateFrom, Journey.timeStart, Vehicule.name AS vehicleName
     FROM Journey
     LEFT JOIN Vehicule ON Vehicule.id = Journey.vehicule
     WHERE Journey.dateFrom >= date("now")
     ORDER BY Journey.dateFrom, Journey.timeStart
     LIMIT 5'
);

$recentJournal = fetch_all(
    'SELECT Journal.timestamp, Journal.label, Person.firstName, Person.lastName
     FROM Journal
     LEFT JOIN Person ON Person.id = Journal.person
     ORDER BY Journal.timestamp DESC
     LIMIT 8'
);

render_header('Tableau de bord', $user);
?>
<div class="row">
    <?php foreach ($stats as $label => $value): ?>
        <div class="col s12 m6 l3">
            <div class="card-panel metric">
                <h5><?= e((string) $value) ?></h5>
                <p><?= e($label) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row">
    <div class="col s12 l7">
        <div class="soft-box">
            <h5>Prochaines remontées</h5>
            <table class="striped">
                <thead>
                <tr>
                    <th>Libellé</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Véhicule</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($nextJourneys as $journey): ?>
                    <tr>
                        <td><?= e($journey['Label']) ?></td>
                        <td><?= e(format_date($journey['dateFrom'])) ?></td>
                        <td><?= e($journey['timeStart']) ?></td>
                        <td><?= e($journey['vehicleName']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col s12 l5">
        <div class="soft-box">
            <h5>Journal récent</h5>
            <ul class="collection">
                <?php foreach ($recentJournal as $entry): ?>
                    <li class="collection-item">
                        <strong><?= e($entry['label']) ?></strong><br>
                        <small><?= e(($entry['firstName'] ?? 'Système') . ' ' . ($entry['lastName'] ?? '')) ?> - <?= e($entry['timestamp']) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php render_footer(); ?>
