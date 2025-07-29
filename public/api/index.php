<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json');

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $path);
if (count($parts) < 2 || $parts[0] !== 'api') {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

$table = $parts[1];
$id = $parts[2] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $table, $id);
            break;
        case 'POST':
            handlePost($pdo, $table);
            break;
        case 'PUT':
            if ($id === null) {
                http_response_code(400);
                echo json_encode(['error' => 'ID required for update']);
            } else {
                handlePut($pdo, $table, $id);
            }
            break;
        case 'DELETE':
            if ($id === null) {
                http_response_code(400);
                echo json_encode(['error' => 'ID required for delete']);
            } else {
                handleDelete($pdo, $table, $id);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet(PDO $pdo, string $table, ?string $id): void {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM \"$table\" WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM \"$table\"");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
    }
}

function handlePost(PDO $pdo, string $table): void {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    $columns = array_keys($data);
    $placeholders = array_map(fn($c) => ':' . $c, $columns);
    $sql = "INSERT INTO \"$table\" (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $id = $pdo->lastInsertId();
    echo json_encode(['id' => $id]);
}

function handlePut(PDO $pdo, string $table, string $id): void {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    $set = [];
    foreach ($data as $col => $val) {
        $set[] = "\"$col\" = :$col";
    }
    $sql = "UPDATE \"$table\" SET " . implode(', ', $set) . " WHERE id = :id";
    $data['id'] = $id;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    echo json_encode(['updated' => $stmt->rowCount()]);
}
?>
