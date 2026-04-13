<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    flash('error', 'Email / identifiant et mot de passe requis.');
    redirect('index.php');
}

$stmt = db()->prepare('SELECT * FROM Person WHERE lower(email) = lower(?) OR lower(username) = lower(?) LIMIT 1');
$stmt->execute([$email, $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    flash('error', 'Identifiants invalides.');
    redirect('index.php');
}

$_SESSION['user_id'] = $user['id'];
save_journal((int) $user['id'], 'Connexion');
flash('success', 'Connexion reussie.');
redirect('dashboard.php');
