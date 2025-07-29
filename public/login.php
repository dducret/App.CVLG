<?php
session_start();
require __DIR__ . '/lang.php';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
if (!$email || !$password) {
    $_SESSION['login_error'] = 'Email and password required';
    $_SESSION['login_error'] = t('error_required');
    header('Location: index.php');
    exit;
}
// Fetch all persons via the API
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$apiUrl = $scheme . '://' . $host . '/api/Person';
$response = @file_get_contents($apiUrl);
if ($response === false) {
    $_SESSION['login_error'] = 'Unable to contact API';
    $_SESSION['login_error'] = t('error_contact_api');
    header('Location: index.php');
    exit;
}
$persons = json_decode($response, true);
$user = null;
foreach ($persons as $p) {
    if (isset($p['email']) && strtolower($p['email']) === strtolower($email)) {
        $user = $p;
        break;
    }
}
if (!$user) {
    $_SESSION['login_error'] = 'Invalid credentials';
    $_SESSION['login_error'] = t('error_credentials');
    header('Location: index.php');
    exit;
}
$hash = $user['password'] ?? '';
if (!password_verify($password, $hash) && $password !== $hash) {
    $_SESSION['login_error'] = 'Invalid credentials';
    $_SESSION['login_error'] = t('error_credentials');
    header('Location: index.php');
    exit;
}
$_SESSION['user_id'] = $user['id'];
header('Location: dashboard.php');
exit;
