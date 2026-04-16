<?php

function navigation_links(array $user): array
{
    $adminLinks = [
        'dashboard.php' => t('nav_dashboard', 'Tableau de bord'),
        'members.php' => t('nav_members', 'Membres'),
        'dues.php' => t('nav_dues', 'Cotisations'),
        'journeys.php' => t('nav_journeys', 'Remontées'),
        'drivers.php' => t('nav_drivers', 'Chauffeurs'),
        'vehicles.php' => t('nav_vehicles', 'Véhicules'),
        'managers.php' => t('nav_managers', 'Managers'),
        'configuration.php' => t('nav_configuration', 'Configuration'),
        'stripe_transactions.php' => 'Paiements Stripe',
        'exports.php' => t('nav_exports', 'Exports'),
    ];

    if (($user['role'] ?? '') === 'L') {
        unset(
            $adminLinks['members.php'],
            $adminLinks['dues.php'],
            $adminLinks['managers.php'],
            $adminLinks['configuration.php'],
            $adminLinks['exports.php']
        );
    }

    if (can_manage_communications($user)) {
        $adminLinks['communications.php'] = t('nav_communications', 'Communication');
    }

    $memberLinks = [
        'profile.php' => t('nav_profile', 'Mon profil'),
        'my_dues.php' => t('nav_my_dues', 'Mes cotisations'),
        'tickets.php' => t('nav_tickets', 'Mes tickets'),
        'bookings.php' => t('nav_bookings', 'Réservations'),
        'my_journeys.php' => t('nav_my_journeys', 'Mon historique'),
    ];

    if (is_admin_like($user) || can_manage_journeys($user)) {
        unset(
            $memberLinks['profile.php'],
            $memberLinks['my_dues.php'],
            $memberLinks['tickets.php'],
            $memberLinks['bookings.php'],
            $memberLinks['my_journeys.php']
        );

        return $adminLinks + $memberLinks;
    }

    return $memberLinks;
}

function render_header(string $title, array $user, array $options = []): void
{
    $nav = navigation_links($user);
    $flash = flash('consume');
    $current = basename($_SERVER['PHP_SELF']);
    $homeHref = landing_page_for_user($user);
    $brandingLogo = 'assets/images/branding/cvlg-logo.svg';
    $brandingFavicon = 'assets/images/branding/favicon.ico';
    ?>
<!DOCTYPE html>
<html lang="<?= e(t('html_lang', current_lang())) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= e($brandingFavicon) ?>">
    <?php render_materialize_css(); ?>
    <style>
        body { background: #f5f7fb; }
        nav { background: #16324f; }
        main { padding: 24px 0 48px; }
        .brand-logo {
            display: inline-flex !important;
            align-items: center;
            gap: 12px;
            height: 64px;
            font-size: 1.35rem !important;
            padding-left: 16px !important;
            white-space: nowrap;
        }
        .brand-logo img {
            width: 42px;
            height: 42px;
            object-fit: contain;
            flex: 0 0 auto;
        }
        .brand-logo .brand-text {
            display: inline-block;
            line-height: 1;
        }
        .mobile-menu-trigger {
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            width: 56px;
            height: 64px;
            margin-left: 8px;
            color: #ffffff;
            line-height: 1;
        }
        .mobile-menu-trigger .hamburger-glyph {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            transform: translateY(-1px);
        }
        .mobile-nav { padding-top: 12px; }
        .mobile-nav li a.active { font-weight: 700; background: #edf3fa; }
        .page-actions .btn { margin-right: 8px; margin-bottom: 8px; }
        .card-panel.metric { min-height: 130px; }
        .soft-box { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 10px 24px rgba(18, 39, 68, 0.08); margin-bottom: 24px; }
        .nav-links a.active { font-weight: 700; text-decoration: underline; }
        table.striped tbody tr:nth-child(odd) { background: #f8fbff; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #edf3fa; font-size: 0.85rem; }
        @media (max-width: 992px) {
            .mobile-menu-trigger { display: inline-flex; }
            .brand-logo {
                left: 50% !important;
                transform: translateX(-50%) !important;
                padding-left: 0 !important;
            }
            .brand-logo img {
                width: 36px;
                height: 36px;
            }
            .brand-logo .brand-text {
                font-size: 1.15rem;
            }
        }
    </style>
</head>
<body>
<nav>
    <div class="nav-wrapper">
        <a href="#" data-target="nav-mobile-menu" class="sidenav-trigger mobile-menu-trigger hide-on-large-only" aria-label="<?= e(t('menu', 'Menu')) ?>">
            <span class="hamburger-glyph" aria-hidden="true">&#9776;</span>
        </a>
        <a href="<?= e($homeHref) ?>" class="brand-logo" aria-label="CVLG">
            <img src="<?= e($brandingLogo) ?>" alt="Logo CVLG">
            <span class="brand-text">CVLG</span>
        </a>
        <ul id="nav-mobile" class="right hide-on-med-and-down nav-links">
            <?php foreach ($nav as $href => $label): ?>
                <li><a class="<?= $current === $href ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a></li>
            <?php endforeach; ?>
            <li><a href="logout.php"><?= e(t('logout', 'Déconnexion')) ?></a></li>
        </ul>
    </div>
</nav>
<ul id="nav-mobile-menu" class="sidenav mobile-nav">
    <?php foreach ($nav as $href => $label): ?>
        <li><a class="<?= $current === $href ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
    <li><a href="logout.php"><?= e(t('logout', 'Déconnexion')) ?></a></li>
</ul>
<main class="container">
    <div class="soft-box">
        <div class="row" style="margin-bottom: 0;">
            <div class="col s12 m8">
                <h4 style="margin-top: 0;"><?= e($title) ?></h4>
                <p style="margin-bottom: 0;">
                    <?= e(trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''))) ?>
                    <span class="pill"><?= e(t('label_role', 'Role')) ?> <?= e($user['role']) ?></span>
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
    <?php render_flash_message($flash); ?>
<?php
}

function render_footer(): void
{
    ?>
</main>
<?php render_materialize_js(['FormSelect', 'Tabs', 'Modal', 'Sidenav']); ?>
</body>
</html>
<?php
}
