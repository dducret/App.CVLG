<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$flash = flash('consume');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('login_title', 'Connexion CVLG')) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: linear-gradient(135deg, #16324f, #2b5876 55%, #f6f9fc); }
        .login-shell { width: min(960px, 92vw); display: grid; grid-template-columns: 1.1fr 0.9fr; background: white; border-radius: 22px; overflow: hidden; box-shadow: 0 30px 80px rgba(7, 25, 47, 0.25); }
        .login-side { padding: 48px; color: white; background: linear-gradient(180deg, rgba(10, 36, 61, 0.92), rgba(17, 74, 106, 0.92)), url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80') center/cover; }
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
            <h3>CVLG</h3>
            <p>Version PHP de l'application de gestion du club de vol libre.</p>
            <p>Cette base couvre l'administration, les cotisations, les remontees, les tickets et les reservations membres.</p>
            <div class="credentials">
                <p>Comptes de demo :</p>
                <p><code>admin@cvlg.local / admin123</code></p>
                <p><code>logistique@cvlg.local / logistique123</code></p>
                <p><code>membre@cvlg.local / membre123</code></p>
            </div>
        </section>
        <section class="login-form">
            <h4><?= e(t('login_title', 'Connexion CVLG')) ?></h4>
            <p>Connectez-vous avec votre email ou votre nom d'utilisateur.</p>
            <?php if ($flash): ?>
                <div class="card-panel <?= $flash['type'] === 'error' ? 'red lighten-4 red-text text-darken-3' : 'green lighten-4 green-text text-darken-3' ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>
            <form method="post" action="login.php">
                <div class="input-field">
                    <input id="email" type="text" name="email" required>
                    <label for="email"><?= e(t('email', 'Email ou identifiant')) ?></label>
                </div>
                <div class="input-field">
                    <input id="password" type="password" name="password" required>
                    <label for="password"><?= e(t('password', 'Mot de passe')) ?></label>
                </div>
                <button class="btn waves-effect waves-light" type="submit"><?= e(t('login', 'Connexion')) ?></button>
            </form>
        </section>
    </div>
</body>
</html>
