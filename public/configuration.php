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
$smtpKeys = [
    'smtp_host' => ['label' => 'Serveur SMTP', 'type' => 'text', 'help' => 'Nom DNS ou IP du serveur SMTP.'],
    'smtp_port' => ['label' => 'Port SMTP', 'type' => 'number', 'help' => 'Port TLS explicite, 587 par defaut.'],
    'smtp_username' => ['label' => 'Utilisateur SMTP', 'type' => 'text', 'help' => 'Compte utilise pour l authentification SMTP.'],
    'smtp_password' => ['label' => 'Mot de passe SMTP', 'type' => 'password', 'help' => 'Mot de passe du compte SMTP.'],
    'smtp_from_email' => ['label' => 'Email expediteur', 'type' => 'email', 'help' => 'Adresse From utilisee pour l envoi.'],
    'smtp_from_name' => ['label' => 'Nom expediteur', 'type' => 'text', 'help' => 'Nom affiche dans le client email.'],
    'smtp_reply_to' => ['label' => 'Reply-To', 'type' => 'email', 'help' => 'Optionnel. Adresse de reponse si differente de l expediteur.'],
];
$stripeKeys = [
    'app_base_url' => ['label' => 'URL publique de l application', 'type' => 'url', 'help' => 'URL absolue utilisee pour les retours Stripe, par exemple https://club.exemple.ch.'],
    'stripe_publishable_key' => ['label' => 'Cle publique Stripe', 'type' => 'text', 'help' => 'Commence generalement par pk_.'],
    'stripe_secret_key' => ['label' => 'Cle secrete Stripe', 'type' => 'password', 'help' => 'Commence generalement par sk_. Utilisee cote serveur.'],
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
                <h5 style="margin-top: 32px;">Email SMTP</h5>
                <p>Configuration du compte expediteur pour l envoi effectif depuis la page communication en TLS sur le port 587 par defaut.</p>
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
                <p>Activez Stripe pour les achats de tickets et le paiement des cotisations membres. L URL publique doit etre accessible depuis Internet pour que Stripe puisse rediriger l utilisateur apres paiement.</p>
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
