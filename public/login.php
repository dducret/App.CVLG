<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$selectedLanguage = set_lang($_POST['lang'] ?? current_lang());
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    flash('error', t('flash_login_required_fields', 'Email / identifiant et mot de passe requis.'));
    redirect('index.php');
}

$stmt = db()->prepare('SELECT * FROM Person WHERE lower(email) = lower(?) OR lower(username) = lower(?) LIMIT 1');
$stmt->execute([$email, $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    flash('error', t('flash_login_invalid', 'Identifiants invalides.'));
    redirect('index.php');
}

$_SESSION['user_id'] = $user['id'];
set_lang($user['language'] ?? $selectedLanguage);
save_journal((int) $user['id'], 'Connexion');
flash('success', t('flash_login_success', 'Connexion reussie.'));
redirect(landing_page_for_user(current_user(true)));
