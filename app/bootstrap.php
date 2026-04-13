<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once dirname(__DIR__) . '/public/lang.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

$pdo = db();
