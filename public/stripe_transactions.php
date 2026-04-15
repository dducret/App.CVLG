<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R', 'L', 'C']);
$stripeEnabled = stripe_supports_checkout();
$stripeError = null;
$remoteSessions = [];
$balanceTransactions = [];

if ($stripeEnabled) {
    try {
        $remoteSessions = stripe_sync_recent_sessions(25);
        $balanceTransactions = stripe_list_balance_transactions(25);
    } catch (Throwable $exception) {
        $stripeError = $exception->getMessage();
    }
}

$payments = fetch_all(
    "SELECT Payment.*, Person.firstName, Person.lastName, MemberYearFee.id AS dueId, YearFee.year AS dueYear
     FROM Payment
     INNER JOIN Person ON Person.id = Payment.person
     LEFT JOIN MemberYearFee ON MemberYearFee.id = Payment.memberYearFee
     LEFT JOIN YearFee ON YearFee.id = MemberYearFee.yearFee
     ORDER BY Payment.createdAt DESC, Payment.id DESC
     LIMIT 100"
);

render_header('Paiements Stripe', $user);
?>
<div class="row">
    <div class="col s12">
        <div class="soft-box">
            <h5>Etat de la configuration</h5>
            <p>Stripe actif: <strong><?= $stripeEnabled ? 'oui' : 'non' ?></strong></p>
            <p>URL publique: <strong><?= e(setting('app_base_url', 'non configuree')) ?></strong></p>
            <p>Devise: <strong><?= e(strtoupper(stripe_currency())) ?></strong></p>
            <?php if ($stripeError): ?>
                <div class="card-panel red lighten-4 red-text text-darken-3"><?= e($stripeError) ?></div>
            <?php endif; ?>
            <?php if (!$stripeEnabled): ?>
                <p>Renseignez les cles Stripe dans <a href="configuration.php">Configuration</a> pour activer les paiements membres.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col s12 l8">
        <div class="soft-box">
            <h5>Journal local des paiements</h5>
            <table class="striped">
                <thead><tr><th>Date</th><th>Membre</th><th>Type</th><th>Montant</th><th>Statut</th><th>Reference</th></tr></thead>
                <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= e(format_datetime($payment['createdAt'])) ?></td>
                        <td><?= e(trim($payment['firstName'] . ' ' . $payment['lastName'])) ?></td>
                        <td>
                            <?= e($payment['kind']) ?>
                            <?php if (!empty($payment['dueYear'])): ?>
                                (<?= e((string) $payment['dueYear']) ?>)
                            <?php elseif (!empty($payment['quantity'])): ?>
                                (<?= e((string) $payment['quantity']) ?> ticket(s))
                            <?php endif; ?>
                        </td>
                        <td><?= e(format_money($payment['amount'], (string) ($payment['currency'] ?: stripe_currency()))) ?></td>
                        <td><?= e($payment['status']) ?></td>
                        <td><?= e($payment['providerSessionId'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$payments): ?>
                    <tr><td colspan="6">Aucun paiement enregistre.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col s12 l4">
        <div class="soft-box">
            <h5>Sessions Checkout Stripe</h5>
            <table class="striped">
                <thead><tr><th>Date</th><th>Montant</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($remoteSessions as $session): ?>
                    <tr>
                        <td><?= e(format_datetime(date('Y-m-d H:i:s', (int) ($session['created'] ?? time())))) ?></td>
                        <td><?= e(format_money(stripe_amount_from_minor_units((int) ($session['amount_total'] ?? 0), (string) ($session['currency'] ?? stripe_currency())), (string) ($session['currency'] ?? stripe_currency()))) ?></td>
                        <td><?= e((string) ($session['payment_status'] ?? $session['status'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$remoteSessions): ?>
                    <tr><td colspan="3">Aucune session distante.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="soft-box">
            <h5>Releve Stripe</h5>
            <table class="striped">
                <thead><tr><th>Date</th><th>Brut</th><th>Net</th><th>Type</th></tr></thead>
                <tbody>
                <?php foreach ($balanceTransactions as $transaction): ?>
                    <tr>
                        <td><?= e(format_datetime(date('Y-m-d H:i:s', (int) ($transaction['created'] ?? time())))) ?></td>
                        <td><?= e(format_money(stripe_amount_from_minor_units((int) ($transaction['amount'] ?? 0), (string) ($transaction['currency'] ?? stripe_currency())), (string) ($transaction['currency'] ?? stripe_currency()))) ?></td>
                        <td><?= e(format_money(stripe_amount_from_minor_units((int) ($transaction['net'] ?? 0), (string) ($transaction['currency'] ?? stripe_currency())), (string) ($transaction['currency'] ?? stripe_currency()))) ?></td>
                        <td><?= e((string) ($transaction['type'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$balanceTransactions): ?>
                    <tr><td colspan="4">Aucun releve Stripe disponible.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php render_footer(); ?>
