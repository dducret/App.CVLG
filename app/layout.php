<?php

function navigation_links(array $user): array
{
    $adminLinks = [
        'dashboard.php' => 'Tableau de bord',
        'members.php' => 'Membres',
        'dues.php' => 'Cotisations',
        'journeys.php' => 'Remontees',
        'drivers.php' => 'Chauffeurs',
        'vehicles.php' => 'Vehicules',
        'managers.php' => 'Managers',
        'configuration.php' => 'Configuration',
        'exports.php' => 'Exports',
    ];

    if (can_manage_communications($user)) {
        $adminLinks['communications.php'] = 'Communication';
    }

    $memberLinks = [
        'profile.php' => 'Mon profil',
        'my_dues.php' => 'Mes cotisations',
        'tickets.php' => 'Mes tickets',
        'bookings.php' => 'Reservations',
        'my_journeys.php' => 'Mon historique',
    ];

    return is_admin_like($user) || can_manage_journeys($user) ? $adminLinks + $memberLinks : $memberLinks;
}

function render_header(string $title, array $user, array $options = []): void
{
    $nav = navigation_links($user);
    $flash = flash('consume');
    $current = basename($_SERVER['PHP_SELF']);
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        body { background: #f5f7fb; }
        nav { background: #16324f; }
        main { padding: 24px 0 48px; }
        .brand-logo { font-size: 1.5rem !important; padding-left: 16px !important; }
        .page-actions .btn { margin-right: 8px; margin-bottom: 8px; }
        .card-panel.metric { min-height: 130px; }
        .soft-box { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 10px 24px rgba(18, 39, 68, 0.08); margin-bottom: 24px; }
        .nav-links a.active { font-weight: 700; text-decoration: underline; }
        table.striped tbody tr:nth-child(odd) { background: #f8fbff; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #edf3fa; font-size: 0.85rem; }
    </style>
</head>
<body>
<nav>
    <div class="nav-wrapper">
        <a href="dashboard.php" class="brand-logo">CVLG</a>
        <ul id="nav-mobile" class="right hide-on-med-and-down nav-links">
            <?php foreach ($nav as $href => $label): ?>
                <li><a class="<?= $current === $href ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a></li>
            <?php endforeach; ?>
            <li><a href="logout.php">Deconnexion</a></li>
        </ul>
    </div>
</nav>
<main class="container">
    <div class="soft-box">
        <div class="row" style="margin-bottom: 0;">
            <div class="col s12 m8">
                <h4 style="margin-top: 0;"><?= e($title) ?></h4>
                <p style="margin-bottom: 0;">
                    <?= e(trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''))) ?>
                    <span class="pill">Role <?= e($user['role']) ?></span>
                </p>
            </div>
            <div class="col s12 m4 right-align page-actions">
                <?php if (!empty($options['actions'])): ?>
                    <?php foreach ($options['actions'] as $action): ?>
                        <a class="btn <?= e($action['class'] ?? '') ?>" href="<?= e($action['href']) ?>"><?= e($action['label']) ?></a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($flash): ?>
        <div class="card-panel <?= $flash['type'] === 'error' ? 'red lighten-4 red-text text-darken-3' : 'green lighten-4 green-text text-darken-3' ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>
<?php
}

function render_footer(): void
{
    ?>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    M.FormSelect.init(document.querySelectorAll('select'));
    M.Tabs.init(document.querySelectorAll('.tabs'));
    M.Modal.init(document.querySelectorAll('.modal'));
});
</script>
</body>
</html>
<?php
}
