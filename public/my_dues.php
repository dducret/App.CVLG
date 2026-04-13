<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();

if (!empty($_GET['pay']) && !empty($user['memberId'])) {
    db()->prepare("UPDATE MemberYearFee SET status = 'paid', date = ?, paymentMethod = ? WHERE id = ? AND member = ?")
        ->execute([today_iso(), 'stripe-simule', (int) $_GET['pay'], (int) $user['memberId']]);
    flash('success', 'Cotisation marquee comme payee.');
    redirect('my_dues.php');
}

$dues = fetch_all(
    'SELECT MemberYearFee.*, YearFee.year
     FROM MemberYearFee
     INNER JOIN YearFee ON YearFee.id = MemberYearFee.yearFee
     WHERE MemberYearFee.member = ?
     ORDER BY YearFee.year DESC',
    [(int) $user['memberId']]
);

render_header('Mes cotisations', $user);
?>
<div class="row">
    <div class="col s12 l8">
        <div class="soft-box">
            <table class="striped">
                <thead><tr><th>Annee</th><th>Montant</th><th>Statut</th><th>Paiement</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($dues as $due): ?>
                    <tr>
                        <td><?= e((string) $due['year']) ?></td>
                        <td><?= e(format_money($due['amount'])) ?></td>
                        <td><?= e($due['status']) ?></td>
                        <td><?= e($due['paymentMethod'] ?: '-') ?> <?= $due['date'] ? '(' . e(format_date($due['date'])) . ')' : '' ?></td>
                        <td><?php if ($due['status'] !== 'paid'): ?><a class="btn-small green" href="?pay=<?= (int) $due['id'] ?>">Payer</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php render_footer(); ?>
