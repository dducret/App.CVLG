<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (current_user()) {
    redirect(landing_page_for_user(current_user()));
}

$flash = flash('consume');
$brandingLogo = 'assets/images/branding/cvlg-logo.svg';
$brandingFavicon = 'assets/images/branding/favicon.ico';
?>
<!DOCTYPE html>
<html lang="<?= e(t('html_lang', current_lang())) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('login_title', 'Connexion CVLG')) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= e($brandingFavicon) ?>">
    <?php render_materialize_css(); ?>
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: linear-gradient(135deg, #16324f, #2b5876 55%, #f6f9fc); }
        .login-shell { width: min(960px, 92vw); display: grid; grid-template-columns: 1.1fr 0.9fr; background: white; border-radius: 22px; overflow: hidden; box-shadow: 0 30px 80px rgba(7, 25, 47, 0.25); }
        .login-side {
            padding: 48px;
            color: white;
            background:
                radial-gradient(circle at top right, rgba(125, 196, 255, 0.28), transparent 34%),
                radial-gradient(circle at bottom left, rgba(255, 255, 255, 0.12), transparent 30%),
                linear-gradient(180deg, rgba(10, 36, 61, 0.96), rgba(17, 74, 106, 0.94));
        }
        .login-brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }
        .login-brand img {
            width: 52px;
            height: 52px;
            object-fit: contain;
            flex: 0 0 auto;
        }
        .login-brand span {
            font-size: 2.1rem;
            font-weight: 600;
            line-height: 1;
        }
        .login-form { padding: 42px; }
        .credentials { margin-top: 32px; font-size: 0.95rem; }
        .credentials code { background: rgba(255, 255, 255, 0.16); padding: 2px 6px; border-radius: 6px; }
        @media (max-width: 800px) {
            .login-shell { grid-template-columns: 1fr; }
            .login-side { display: none; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <section class="login-side">
            <div class="login-brand" aria-label="CVLG">
                <img src="<?= e($brandingLogo) ?>" alt="Logo CVLG">
                <span><?= e(t('login_side_title', 'CVLG')) ?></span>
            </div>
            <p><?= e(t('login_side_description_1', 'Version PHP de l\'application de gestion du club de vol libre.')) ?></p>
            <p><?= e(t('login_side_description_2', 'Cette base couvre l\'administration, les cotisations, les remontees, les tickets et les reservations membres.')) ?></p>
            <div class="credentials">
                <p><?= e(t('demo_accounts', 'Comptes de demo :')) ?></p>
                <p><code>admin@cvlg.local / admin123</code></p>
                <p><code>logistique@cvlg.local / logistique123</code></p>
                <p><code>membre@cvlg.local / membre123</code></p>
            </div>
        </section>
        <section class="login-form">
            <h4><?= e(t('login_title', 'Connexion CVLG')) ?></h4>
            <p><?= e(t('login_intro', 'Connectez-vous avec votre email ou votre nom d\'utilisateur.')) ?></p>
            <?php render_flash_message($flash); ?>
            <form method="post" action="login.php">
                <div class="input-field">
                    <select id="lang" name="lang" data-lang-switcher>
                        <?php foreach (supported_languages() as $code => $label): ?>
                            <option value="<?= e($code) ?>" <?= current_lang() === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="lang"><?= e(t('login_language', 'Langue')) ?></label>
                </div>
                <div class="input-field">
                    <input id="email" type="text" name="email" required>
                    <label for="email"><?= e(t('email_or_username', 'Email ou identifiant')) ?></label>
                </div>
                <div class="input-field">
                    <input id="password" type="password" name="password" required>
                    <label for="password"><?= e(t('password', 'Mot de passe')) ?></label>
                </div>
                <button class="btn waves-effect waves-light" type="submit"><?= e(t('login_submit', 'Connexion')) ?></button>
            </form>
        </section>
    </div>
<?php render_materialize_js(['FormSelect']); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var languageSwitcher = document.querySelector('[data-lang-switcher]');
    if (languageSwitcher) {
        languageSwitcher.addEventListener('change', function () {
            window.location.href = 'index.php?lang=' + encodeURIComponent(languageSwitcher.value);
        });
    }
});
</script>
</body>
</html>
