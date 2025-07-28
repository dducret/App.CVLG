<?php
$dbFile = __DIR__ . '/../../db/database.sqlite';
$new = !file_exists($dbFile);
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($new) {
    $schema = file_get_contents(__DIR__ . '/../../Documentation/appcvlg.db.sql');
    $pdo->exec($schema);
}
?>
