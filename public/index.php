<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
require __DIR__ . '/lang.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= t('login_title') ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
</head>
<body class="container">
    <p class="right-align">
        <?= t('language') ?>:
        <a href="?lang=en">EN</a> |
        <a href="?lang=fr">FR</a> |
        <a href="?lang=de">DE</a> |
        <a href="?lang=it">IT</a>
    </p>
    <h3 class="center-align"><?= t('login_title') ?></h3>
    <?php if ($error): ?>
        <div class="card-panel red lighten-2 white-text"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="row">
        <form class="col s12" method="post" action="login.php">
            <div class="row">
                <div class="input-field col s12">
                    <input id="email" type="email" name="email" class="validate" required>
                    <label for="email"><?= t('email') ?></label>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="password" type="password" name="password" class="validate" required>
                    <label for="password"><?= t('password') ?></label>
                </div>
            </div>
            <div class="row center-align">
                <button class="btn waves-effect waves-light" type="submit"><?= t('login') ?></button>
            </div>
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
