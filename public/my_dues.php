<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();
$stripeEnabled = stripe_supports_checkout();

if (($_GET['stripe_status'] ?? '') === 'cancel') {
    flash('error', 'Paiement Stripe annule.');
    redirect('my_dues.php');
}

if (($_GET['stripe_status'] ?? '') === 'success' && !empty($_GET['session_id'])) {
    try {
        $result = stripe_sync_checkout_session((string) $_GET['session_id'], (int) $user['id']);
        if ($result['status'] === 'paid') {
            flash('success', 'Cotisation payee via Stripe.');
        } else {
            flash('error', 'Le paiement Stripe est encore en attente.');
        }
    } catch (Throwable $exception) {
        flash('error', 'Verification Stripe impossible: ' . $exception->getMessage());
    }
    redirect('my_dues.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout_due' && !empty($user['memberId'])) {
    $due = fetch_one(
        'SELECT MemberYearFee.*, YearFee.year
         FROM MemberYearFee
         INNER JOIN YearFee ON YearFee.id = MemberYearFee.yearFee
         WHERE MemberYearFee.id = ?
           AND MemberYearFee.member = ?',
        [(int) ($_POST['due_id'] ?? 0), (int) $user['memberId']]
    );

    if (!$due) {
        flash('error', 'Cotisation introuvable.');
        redirect('my_dues.php');
    }

    if ($due['status'] === 'paid') {
        flash('success', 'Cette cotisation est deja payee.');
        redirect('my_dues.php');
    }

    if (!$stripeEnabled) {
        flash('error', 'Stripe n est pas configure par l administration.');
        redirect('my_dues.php');
    }

    try {
        $session = stripe_create_checkout_for_due($user, $due);
        header('Location: ' . ($session['url'] ?? 'my_dues.php'));
        exit;
    } catch (Throwable $exception) {
        flash('error', 'Creation du paiement Stripe impossible: ' . $exception->getMessage());
        redirect('my_dues.php');
    }
}

$dues = fetch_all(
    'SELECT MemberYearFee.*, YearFee.year
     FROM MemberYearFee
     INNER JOIN YearFee ON YearFee.id = MemberYearFee.yearFee
    WHERE MemberYearFee.member = ?
    ORDER BY YearFee.year DESC',
    [(int) $user['memberId']]
);
$recentPayments = fetch_all(
    "SELECT *
     FROM Payment
     WHERE person = ?
       AND kind = 'membership_fee'
     ORDER BY createdAt DESC, id DESC
     LIMIT 10",
    [(int) $user['id']]
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
                        <td>
                            <?php if ($due['status'] !== 'paid' && $stripeEnabled): ?>
                                <form method="post" style="margin:0;">
                                    <input type="hidden" name="action" value="checkout_due">
                                    <input type="hidden" name="due_id" value="<?= (int) $due['id'] ?>">
                                    <button class="btn-small green" type="submit">Payer avec Stripe</button>
                                </form>
                            <?php elseif ($due['status'] !== 'paid'): ?>
                                <span class="grey-text">Stripe non configure</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col s12 l4">
        <div class="soft-box">
            <h5>Historique Stripe</h5>
            <table class="striped">
                <thead><tr><th>Date</th><th>Montant</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($recentPayments as $payment): ?>
                    <tr>
                        <td><?= e(format_datetime($payment['createdAt'])) ?></td>
                        <td><?= e(format_money($payment['amount'])) ?></td>
                        <td><?= e($payment['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentPayments): ?>
                    <tr><td colspan="3">Aucun paiement Stripe.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php render_footer(); ?>
