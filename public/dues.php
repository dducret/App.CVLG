<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R', 'L', 'C']);
$pdo = db();
$year = (int) ($_GET['year'] ?? date('Y'));

if (isset($_GET['collect'])) {
    $pdo->prepare("UPDATE MemberYearFee SET status = 'paid', date = ?, paymentMethod = ? WHERE id = ?")
        ->execute([today_iso(), 'cash', (int) $_GET['collect']]);
    flash('success', 'Cotisation encaissee.');
    redirect('dues.php?year=' . $year);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    $targetYear = (int) ($_POST['target_year'] ?? date('Y'));
    $fees = [
        'actif' => (float) ($_POST['fee_actif'] ?? 120),
        'honoraire' => (float) ($_POST['fee_honoraire'] ?? 0),
        'sympathisant' => (float) ($_POST['fee_sympathisant'] ?? 30),
        'partenaire' => (float) ($_POST['fee_partenaire'] ?? 120),
    ];

    $pdo->beginTransaction();
    foreach ($fees as $type => $price) {
        $pdo->prepare('INSERT INTO YearFee(year, type, price) VALUES (?, ?, ?) ON CONFLICT(year, type) DO UPDATE SET price = excluded.price')
            ->execute([$targetYear, $type, $price]);
    }

    $yearFees = fetch_all('SELECT * FROM YearFee WHERE year = ?', [$targetYear]);
    $yearFeeMap = [];
    foreach ($yearFees as $yearFee) {
        $yearFeeMap[$yearFee['type']] = $yearFee;
    }

    foreach (fetch_all('SELECT id, type FROM Member') as $member) {
        if (!isset($yearFeeMap[$member['type']])) {
            continue;
        }
        $yearFee = $yearFeeMap[$member['type']];
        $pdo->prepare('INSERT OR IGNORE INTO MemberYearFee(member, yearFee, status, amount) VALUES (?, ?, ?, ?)')
            ->execute([$member['id'], $yearFee['id'], 'pending', $yearFee['price']]);
    }
    $pdo->commit();
    flash('success', 'Cotisations generees pour ' . $targetYear . '.');
    redirect('dues.php?year=' . $targetYear);
}

$dues = fetch_all(
    'SELECT MemberYearFee.id, MemberYearFee.status, MemberYearFee.date, MemberYearFee.amount, MemberYearFee.paymentMethod,
            Person.firstName, Person.lastName, Member.type, YearFee.year
     FROM MemberYearFee
     INNER JOIN Member ON Member.id = MemberYearFee.member
     INNER JOIN Person ON Person.id = Member.person
     INNER JOIN YearFee ON YearFee.id = MemberYearFee.yearFee
     WHERE YearFee.year = ?
     ORDER BY MemberYearFee.status, Person.lastName, Person.firstName',
    [$year]
);

render_header('Gestion des cotisations', $user);
?>
<div class="row">
    <div class="col s12 l4">
        <div class="soft-box">
            <h5>Generer les cotisations</h5>
            <form method="post">
                <input type="hidden" name="action" value="generate">
                <div class="input-field"><input type="number" id="target_year" name="target_year" value="<?= e((string) $year) ?>"><label for="target_year" class="active">Annee</label></div>
                <div class="input-field"><input type="number" step="0.01" id="fee_actif" name="fee_actif" value="<?= e(setting('annual_fee_active', '120')) ?>"><label for="fee_actif" class="active">Actif</label></div>
                <div class="input-field"><input type="number" step="0.01" id="fee_honoraire" name="fee_honoraire" value="0"><label for="fee_honoraire" class="active">Honoraire</label></div>
                <div class="input-field"><input type="number" step="0.01" id="fee_sympathisant" name="fee_sympathisant" value="<?= e(setting('annual_fee_supporter', '30')) ?>"><label for="fee_sympathisant" class="active">Sympathisant</label></div>
                <div class="input-field"><input type="number" step="0.01" id="fee_partenaire" name="fee_partenaire" value="120"><label for="fee_partenaire" class="active">Partenaire</label></div>
                <button class="btn" type="submit">Generer</button>
            </form>
        </div>
    </div>
    <div class="col s12 l8">
        <div class="soft-box">
            <form method="get" class="row">
                <div class="input-field col s12 m4"><input type="number" id="year" name="year" value="<?= e((string) $year) ?>"><label for="year" class="active">Annee</label></div>
                <div class="col s12 m2" style="padding-top: 18px;"><button class="btn">Voir</button></div>
            </form>
            <table class="striped">
                <thead><tr><th>Membre</th><th>Type</th><th>Montant</th><th>Statut</th><th>Paiement</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($dues as $due): ?>
                    <tr>
                        <td><?= e($due['firstName'] . ' ' . $due['lastName']) ?></td>
                        <td><?= e($due['type']) ?></td>
                        <td><?= e(format_money($due['amount'])) ?></td>
                        <td><?= e(translate_status($due['status'])) ?></td>
                        <td><?= e($due['paymentMethod'] ?: '-') ?> <?= $due['date'] ? '(' . e(format_date($due['date'])) . ')' : '' ?></td>
                        <td><?php if ($due['status'] !== 'paid'): ?><a class="btn-small green" href="?year=<?= $year ?>&collect=<?= (int) $due['id'] ?>">Encaisser</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php render_footer(); ?>
