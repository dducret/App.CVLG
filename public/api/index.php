<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json');

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $path);
$table = $parts[1] ?? '';
$id = $parts[2] ?? null;

$allowedTables = [
    'Address', 'Person', 'Member', 'Driver', 'Manager', 'Vehicule', 'Journey',
    'Booking', 'Ticket', 'YearFee', 'MemberYearFee', 'Settings', 'Message', 'Content', 'Journal', 'License'
];

if (!in_array($table, $allowedTables, true)) {
    http_response_code(404);
    echo json_encode(['error' => 'Unknown resource']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($id !== null) {
            $stmt = $pdo->prepare("SELECT * FROM \"$table\" WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch() ?: ['error' => 'Not found'];
            if (isset($data['error'])) {
                http_response_code(404);
            }
            echo json_encode($data);
            exit;
        }

        echo json_encode(fetch_all("SELECT * FROM \"$table\""));
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Read-only API in this version']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
