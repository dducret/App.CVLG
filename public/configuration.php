<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R']);
$keys = [
    'club_name' => 'Nom du club',
    'contact_email' => 'Email de contact',
    'ticket_price' => 'Prix du ticket',
    'annual_fee_active' => 'Cotisation actif',
    'annual_fee_supporter' => 'Cotisation sympathisant',
    'booking_window_days' => 'Fenetre de reservation (jours)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($keys as $key => $label) {
        db()->prepare('INSERT INTO Settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
            ->execute([$key, trim((string) ($_POST[$key] ?? ''))]);
    }
    flash('success', 'Configuration enregistree.');
    redirect('configuration.php');
}

render_header('Configuration', $user);
?>
<div class="row">
    <div class="col s12 l8">
        <div class="soft-box">
            <form method="post">
                <?php foreach ($keys as $key => $label): ?>
                    <div class="input-field">
                        <input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e(setting($key, '')) ?>">
                        <label for="<?= e($key) ?>" class="active"><?= e($label) ?></label>
                    </div>
                <?php endforeach; ?>
                <button class="btn" type="submit">Enregistrer</button>
            </form>
        </div>
    </div>
</div>
<?php render_footer(); ?>
