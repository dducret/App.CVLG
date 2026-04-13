<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$supportedLanguages = ['fr', 'en', 'de', 'it'];
$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'fr');
if (!in_array($lang, $supportedLanguages, true)) {
    $lang = 'fr';
}
$_SESSION['lang'] = $lang;

$translations = [
    'fr' => [
        'login_title' => 'Connexion CVLG',
        'email' => 'Email',
        'password' => 'Mot de passe',
        'login' => 'Connexion',
        'logout' => 'Deconnexion',
    ],
    'en' => [
        'login_title' => 'CVLG Login',
        'email' => 'Email',
        'password' => 'Password',
        'login' => 'Login',
        'logout' => 'Logout',
    ],
    'de' => [
        'login_title' => 'CVLG Anmeldung',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'login' => 'Anmelden',
        'logout' => 'Abmelden',
    ],
    'it' => [
        'login_title' => 'Accesso CVLG',
        'email' => 'Email',
        'password' => 'Password',
        'login' => 'Accedi',
        'logout' => 'Disconnetti',
    ],
];

function t(string $key, ?string $fallback = null): string
{
    global $translations, $lang;
    return $translations[$lang][$key] ?? $fallback ?? $key;
}
