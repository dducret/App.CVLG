<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R']);
$generalKeys = [
    'club_name' => 'Nom du club',
    'contact_email' => 'Email de contact',
    'ticket_price' => 'Prix du ticket',
    'annual_fee_active' => 'Cotisation actif',
    'annual_fee_supporter' => 'Cotisation sympathisant',
    'booking_window_days' => 'Fenêtre de réservation (jours)',
];
$smtpKeys = [
    'smtp_host' => ['label' => 'Serveur SMTP', 'type' => 'text', 'help' => 'Nom DNS ou IP du serveur SMTP.'],
    'smtp_port' => ['label' => 'Port SMTP', 'type' => 'number', 'help' => 'Port TLS explicite, 587 par défaut.'],
    'smtp_username' => ['label' => 'Utilisateur SMTP', 'type' => 'text', 'help' => 'Compte utilisé pour l’authentification SMTP.'],
    'smtp_password' => ['label' => 'Mot de passe SMTP', 'type' => 'password', 'help' => 'Mot de passe du compte SMTP.'],
    'smtp_from_email' => ['label' => 'Email expéditeur', 'type' => 'email', 'help' => 'Adresse From utilisée pour l’envoi.'],
    'smtp_from_name' => ['label' => 'Nom expéditeur', 'type' => 'text', 'help' => 'Nom affiché dans le client email.'],
    'smtp_reply_to' => ['label' => 'Reply-To', 'type' => 'email', 'help' => 'Optionnel. Adresse de réponse si différente de l’expéditeur.'],
];
$stripeKeys = [
    'app_base_url' => ['label' => 'URL publique de l’application', 'type' => 'url', 'help' => 'URL absolue utilisée pour les retours Stripe, par exemple https://club.exemple.ch.'],
    'stripe_publishable_key' => ['label' => 'Clé publique Stripe', 'type' => 'text', 'help' => 'Commence généralement par pk_.'],
    'stripe_secret_key' => ['label' => 'Clé secrète Stripe', 'type' => 'password', 'help' => 'Commence généralement par sk_. Utilisée côté serveur.'],
    'stripe_currency' => ['label' => 'Devise Stripe', 'type' => 'text', 'help' => 'Devise ISO en minuscules, par exemple chf ou eur.'],
];
$bookingRules = booking_rule_definitions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($generalKeys as $key => $label) {
        db()->prepare('INSERT INTO Settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
            ->execute([$key, trim((string) ($_POST[$key] ?? ''))]);
    }

    foreach ($smtpKeys as $key => $meta) {
        db()->prepare('INSERT INTO Settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
            ->execute([$key, trim((string) ($_POST[$key] ?? ''))]);
    }

    db()->prepare('INSERT INTO Settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
        ->execute(['stripe_enabled', isset($_POST['stripe_enabled']) ? '1' : '0']);

    foreach ($stripeKeys as $key => $meta) {
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
    flash('success', 'Configuration enregistrée.');
    redirect('configuration.php');
}

render_header('Configuration', $user);
?>
<div class="row">
    <div class="col s12 l8">
        <div class="soft-box">
            <form method="post">
                <h5>Paramètres généraux</h5>
                <?php foreach ($generalKeys as $key => $label): ?>
                    <div class="input-field">
                        <input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e(setting($key, '')) ?>">
                        <label for="<?= e($key) ?>" class="active"><?= e($label) ?></label>
                    </div>
                <?php endforeach; ?>
                <h5 style="margin-top: 32px;">Email SMTP</h5>
                <p>Configuration du compte expéditeur pour l’envoi effectif depuis la page communication en TLS sur le port 587 par défaut.</p>
                <?php foreach ($smtpKeys as $key => $meta): ?>
                    <div class="input-field">
                        <input
                            type="<?= e($meta['type']) ?>"
                            id="<?= e($key) ?>"
                            name="<?= e($key) ?>"
                            value="<?= e(setting($key, $key === 'smtp_port' ? '587' : '')) ?>"
                            <?= $key === 'smtp_port' ? 'min="1"' : '' ?>
                        >
                        <label for="<?= e($key) ?>" class="active"><?= e($meta['label']) ?></label>
                        <span class="helper-text"><?= e($meta['help']) ?></span>
                    </div>
                <?php endforeach; ?>
                <h5 style="margin-top: 32px;">Paiements Stripe</h5>
                <p>Activez Stripe pour les achats de tickets et le paiement des cotisations membres. L’URL publique doit être accessible depuis Internet pour que Stripe puisse rediriger l’utilisateur après paiement.</p>
                <p>
                    <label>
                        <input type="checkbox" name="stripe_enabled" <?= setting('stripe_enabled', '0') === '1' ? 'checked' : '' ?>>
                        <span>Activer Stripe pour les membres</span>
                    </label>
                </p>
                <?php foreach ($stripeKeys as $key => $meta): ?>
                    <div class="input-field">
                        <input
                            type="<?= e($meta['type']) ?>"
                            id="<?= e($key) ?>"
                            name="<?= e($key) ?>"
                            value="<?= e(setting($key, $key === 'stripe_currency' ? 'chf' : '')) ?>"
                        >
                        <label for="<?= e($key) ?>" class="active"><?= e($meta['label']) ?></label>
                        <span class="helper-text"><?= e($meta['help']) ?></span>
                    </div>
                <?php endforeach; ?>
                <h5 style="margin-top: 32px;">Règles de réservation</h5>
                <p>Ces paramètres permettent d'ajuster le comportement des réservations et de la liste d'attente.</p>
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
