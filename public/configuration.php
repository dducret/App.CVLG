<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R']);
$generalKeys = [
    'club_name' => 'Nom du club',
    'contact_email' => 'Email de contact',
    'ticket_price' => 'Prix du ticket',
    'annual_fee_active' => 'Cotisation actif',
    'annual_fee_supporter' => 'Cotisation sympathisant',
    'booking_window_days' => 'Fenetre de reservation (jours)',
];
$bookingRules = booking_rule_definitions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($generalKeys as $key => $label) {
        db()->prepare('INSERT INTO Settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
            ->execute([$key, trim((string) ($_POST[$key] ?? ''))]);
    }

    foreach ($bookingRules as $key => $rule) {
        $value = $rule['type'] === 'boolean'
            ? (isset($_POST[$key]) ? '1' : '0')
            : trim((string) ($_POST[$key] ?? $rule['default']));
        db()->prepare('INSERT INTO Settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
            ->execute([$key, $value]);
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
                <h5>Parametres generaux</h5>
                <?php foreach ($generalKeys as $key => $label): ?>
                    <div class="input-field">
                        <input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e(setting($key, '')) ?>">
                        <label for="<?= e($key) ?>" class="active"><?= e($label) ?></label>
                    </div>
                <?php endforeach; ?>
                <h5 style="margin-top: 32px;">Regles de reservation</h5>
                <p>Ces parametres permettent d'ajuster le comportement des reservations et de la liste d'attente.</p>
                <?php foreach ($bookingRules as $key => $rule): ?>
                    <?php $currentValue = setting($key, $rule['default']); ?>
                    <?php if ($rule['type'] === 'boolean'): ?>
                        <p>
                            <label>
                                <input type="checkbox" name="<?= e($key) ?>" <?= $currentValue === '1' ? 'checked' : '' ?>>
                                <span><?= e($rule['label']) ?></span>
                            </label><br>
                            <small><?= e($rule['help']) ?></small>
                        </p>
                    <?php else: ?>
                        <div class="input-field">
                            <input type="number" min="0" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($currentValue) ?>">
                            <label for="<?= e($key) ?>" class="active"><?= e($rule['label']) ?></label>
                            <span class="helper-text"><?= e($rule['help']) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <button class="btn" type="submit">Enregistrer</button>
            </form>
        </div>
    </div>
</div>
<?php render_footer(); ?>
