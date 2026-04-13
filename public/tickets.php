<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();
$ticketPrice = (float) setting('ticket_price', '9');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    $price = $quantity * $ticketPrice;
    db()->prepare('INSERT INTO Ticket(person, quantity, price, date, used) VALUES (?, ?, ?, ?, 0)')
        ->execute([(int) $user['id'], $quantity, $price, today_iso()]);
    flash('success', 'Pack de tickets ajoute.');
    redirect('tickets.php');
}

$tickets = fetch_all('SELECT * FROM Ticket WHERE person = ? ORDER BY date DESC, id DESC', [(int) $user['id']]);
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
                <button class="btn" type="submit">Acheter</button>
            </form>
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
