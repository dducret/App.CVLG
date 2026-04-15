<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();
$ticketPrice = (float) setting('ticket_price', '9');
$stripeEnabled = stripe_supports_checkout();

if (($_GET['stripe_status'] ?? '') === 'cancel') {
    flash('error', 'Paiement Stripe annule.');
    redirect('tickets.php');
}

if (($_GET['stripe_status'] ?? '') === 'success' && !empty($_GET['session_id'])) {
    try {
        $result = stripe_sync_checkout_session((string) $_GET['session_id'], (int) $user['id']);
        if ($result['status'] === 'paid') {
            flash('success', 'Paiement confirme et tickets ajoutes.');
        } else {
            flash('error', 'Le paiement Stripe est encore en attente.');
        }
    } catch (Throwable $exception) {
        flash('error', 'Verification Stripe impossible: ' . $exception->getMessage());
    }
    redirect('tickets.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    if (!$stripeEnabled) {
        flash('error', 'Stripe n est pas configure par l administration.');
        redirect('tickets.php');
    }

    try {
        $session = stripe_create_checkout_for_tickets($user, $quantity, $ticketPrice);
        header('Location: ' . ($session['url'] ?? 'tickets.php'));
        exit;
    } catch (Throwable $exception) {
        flash('error', 'Creation du paiement Stripe impossible: ' . $exception->getMessage());
    }
    redirect('tickets.php');
}

$tickets = fetch_all('SELECT * FROM Ticket WHERE person = ? ORDER BY date DESC, id DESC', [(int) $user['id']]);
$recentPayments = fetch_all(
    "SELECT *
     FROM Payment
     WHERE person = ?
       AND kind = 'ticket'
     ORDER BY createdAt DESC, id DESC
     LIMIT 10",
    [(int) $user['id']]
);
render_header('Mes tickets', $user);
?>
<div class="row">
    <div class="col s12 l4">
        <div class="soft-box">
            <h5>Acheter des tickets</h5>
            <p>Prix unitaire: <?= e(format_money($ticketPrice)) ?></p>
            <p>Tickets disponibles: <strong><?= remaining_tickets((int) $user['id']) ?></strong></p>
            <form method="post">
                <div class="input-field"><input type="number" id="quantity" name="quantity" value="5" min="1"><label for="quantity" class="active">Quantite</label></div>
                <?php if ($stripeEnabled): ?>
                    <button class="btn" type="submit">Payer avec Stripe</button>
                <?php else: ?>
                    <button class="btn disabled" type="button">Stripe non configure</button>
                    <p class="helper-text" style="display:block;">L administration doit renseigner les cles Stripe et l URL publique dans la configuration.</p>
                <?php endif; ?>
            </form>
        </div>
        <div class="soft-box">
            <h5>Derniers paiements</h5>
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
    <div class="col s12 l8">
        <div class="soft-box">
            <table class="striped">
                <thead><tr><th>Date</th><th>Quantite</th><th>Prix</th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?= e(format_date($ticket['date'])) ?></td>
                        <td><?= e((string) $ticket['quantity']) ?></td>
                        <td><?= e(format_money($ticket['price'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php render_footer(); ?>
