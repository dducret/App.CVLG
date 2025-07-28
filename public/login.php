<?php
session_start();
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
if (!$email || !$password) {
    $_SESSION['login_error'] = 'Email and password required';
    header('Location: index.php');
    exit;
}
// Fetch all persons via the API
$apiUrl = '/api/Person';
$response = @file_get_contents($apiUrl);
if ($response === false) {
    $_SESSION['login_error'] = 'Unable to contact API';
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
    header('Location: index.php');
    exit;
}
$hash = $user['password'] ?? '';
if (!password_verify($password, $hash) && $password !== $hash) {
    $_SESSION['login_error'] = 'Invalid credentials';
    header('Location: index.php');
    exit;
}
$_SESSION['user_id'] = $user['id'];
header('Location: dashboard.php');
exit;
