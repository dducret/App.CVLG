<?php
session_start();
require __DIR__ . '/lang.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= t('welcome') ?></title>
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
    <h3 class="center-align"><?= t('welcome') ?></h3>
    <p><?= t('logged_in') ?></p>
    <div class="center-align">
        <a href="members.php" class="btn"><?= t('member_management') ?></a>
    </div>
    <div class="center-align" style="margin-top:20px;">
        <a href="logout.php" class="btn"><?= t('logout') ?></a>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
