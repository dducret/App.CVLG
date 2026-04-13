<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    save_journal((int) $_SESSION['user_id'], 'Deconnexion');
}

session_destroy();
session_start();
flash('success', 'Session fermee.');
redirect('index.php');
