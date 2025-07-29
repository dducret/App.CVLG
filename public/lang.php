<?php
$status = session_status();
if ($status === PHP_SESSION_NONE) {
    session_start();
}
$supported = ['en', 'fr', 'de', 'it'];
$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'en');
if (!in_array($lang, $supported)) {
    $lang = 'en';
}
$_SESSION['lang'] = $lang;

$translations = [
    'en' => [
        'login_title' => 'CVLG Login',
        'email' => 'Email',
        'password' => 'Password',
        'login' => 'Login',
        'welcome' => 'Welcome',
        'logged_in' => 'You are logged in.',
        'logout' => 'Logout',
        'member_management' => 'Member Management',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'member_type' => 'Member Type',
        'address' => 'Address',
        'street' => 'Street',
        'street_number' => 'Street Number',
        'postal_code' => 'Postal Code',
        'city' => 'City',
        'country' => 'Country',
        'add_member' => 'Add Member',
        'save' => 'Save',
        'language' => 'Language'
        ,'error_required' => 'Email and password required'
        ,'error_contact_api' => 'Unable to contact API'
        ,'error_credentials' => 'Invalid credentials'
    ],
    'fr' => [
        'login_title' => 'Connexion CVLG',
        'email' => 'Email',
        'password' => 'Mot de passe',
        'login' => 'Connexion',
        'welcome' => 'Bienvenue',
        'logged_in' => 'Vous êtes connecté.',
        'logout' => 'Déconnexion',
        'member_management' => 'Gestion des membres',
        'first_name' => 'Prénom',
        'last_name' => 'Nom',
        'member_type' => 'Type de membre',
        'address' => 'Adresse',
        'street' => 'Rue',
        'street_number' => 'Numéro',
        'postal_code' => 'Code postal',
        'city' => 'Ville',
        'country' => 'Pays',
        'add_member' => 'Ajouter un membre',
        'save' => 'Enregistrer',
        'language' => 'Langue'
        ,'error_required' => 'Email et mot de passe requis'
        ,'error_contact_api' => "Impossible d'accéder à l'API"
        ,'error_credentials' => 'Identifiants invalides'
    ],
    'de' => [
        'login_title' => 'CVLG Anmeldung',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'login' => 'Anmelden',
        'welcome' => 'Willkommen',
        'logged_in' => 'Sie sind eingeloggt.',
        'logout' => 'Abmelden',
        'member_management' => 'Mitgliederverwaltung',
        'first_name' => 'Vorname',
        'last_name' => 'Nachname',
        'member_type' => 'Mitgliedstyp',
        'address' => 'Adresse',
        'street' => 'Straße',
        'street_number' => 'Nummer',
        'postal_code' => 'PLZ',
        'city' => 'Stadt',
        'country' => 'Land',
        'add_member' => 'Mitglied hinzufügen',
        'save' => 'Speichern',
        'language' => 'Sprache'
        ,'error_required' => 'E-Mail und Passwort erforderlich'
        ,'error_contact_api' => 'API konnte nicht erreicht werden'
        ,'error_credentials' => 'Ungültige Anmeldedaten'
    ],
    'it' => [
        'login_title' => 'Accesso CVLG',
        'email' => 'Email',
        'password' => 'Password',
        'login' => 'Accedi',
        'welcome' => 'Benvenuto',
        'logged_in' => 'Sei connesso.',
        'logout' => 'Disconnetti',
        'member_management' => 'Gestione membri',
        'first_name' => 'Nome',
        'last_name' => 'Cognome',
        'member_type' => 'Tipo di membro',
        'address' => 'Indirizzo',
        'street' => 'Via',
        'street_number' => 'Numero',
        'postal_code' => 'CAP',
        'city' => 'Città',
        'country' => 'Paese',
        'add_member' => 'Aggiungi membro',
        'save' => 'Salva',
        'language' => 'Lingua'
        ,'error_required' => 'Email e password obbligatorie'
        ,'error_contact_api' => "Impossibile contattare l'API"
        ,'error_credentials' => 'Credenziali non valide'
    ]
];

function t(string $key): string {
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}
?>
