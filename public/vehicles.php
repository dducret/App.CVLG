<?php
session_start();
require __DIR__ . '/lang.php';
require __DIR__ . '/api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Delete vehicle
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM Vehicule WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    header('Location: vehicles.php');
    exit;
}

// Fetch vehicle to edit
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM Vehicule WHERE id = ?');
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Create or update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $registration = $_POST['registration'] ?? '';
    $label = $_POST['label'] ?? '';
    $seats = (int)($_POST['seats'] ?? 0);
    $status = (int)($_POST['status'] ?? 0);
    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare('UPDATE Vehicule SET name=?, registration=?, label=?, seats=?, status=? WHERE id=?');
        $stmt->execute([$name, $registration, $label, $seats, $status, $_POST['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO Vehicule(name, registration, label, seats, status) VALUES (?,?,?,?,?)');
        $stmt->execute([$name, $registration, $label, $seats, $status]);
    }
    header('Location: vehicles.php');
    exit;
}

$vehicles = $pdo->query('SELECT * FROM Vehicule')->fetchAll(PDO::FETCH_ASSOC);
$statusLabels = [0 => t('status_operationnal'), 1 => t('status_maintenance'), 2 => t('status_broken')];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= t('vehicle_management') ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
</head>
<body class="container">
    <p class="right-align">
        <?= t('language') ?>:
        <a href="?lang=en">EN</a> |
        <a href="?lang=fr">FR</a> |
        <a href="?lang=de">DE</a> |
        <a href="?lang=it">IT</a>
    </p>
    <h3 class="center-align"><?= t('vehicle_management') ?></h3>
    <table class="striped">
        <thead>
            <tr>
                <th><?= t('name') ?></th>
                <th><?= t('registration') ?></th>
                <th><?= t('label') ?></th>
                <th><?= t('seats') ?></th>
                <th><?= t('status') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['name']) ?></td>
                    <td><?= htmlspecialchars($v['registration']) ?></td>
                    <td><?= htmlspecialchars($v['label']) ?></td>
                    <td><?= htmlspecialchars($v['seats']) ?></td>
                    <td><?= $statusLabels[$v['status']] ?? $v['status'] ?></td>
                    <td>
                        <a href="vehicles.php?edit=<?= $v['id'] ?>" class="btn-small"><?= t('edit') ?></a>
                        <a href="vehicles.php?delete=<?= $v['id'] ?>" class="btn-small red" onclick="return confirm('<?= t('delete') ?>?');"><?= t('delete') ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4><?= t('add_vehicle') ?></h4>
    <form method="post">
        <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? '') ?>">
        <div class="row">
            <div class="input-field col s6">
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required>
                <label for="name"><?= t('name') ?></label>
            </div>
            <div class="input-field col s6">
                <input type="text" name="registration" id="registration" value="<?= htmlspecialchars($edit['registration'] ?? '') ?>">
                <label for="registration"><?= t('registration') ?></label>
            </div>
        </div>
        <div class="row">
            <div class="input-field col s4">
                <input type="text" name="label" id="label" value="<?= htmlspecialchars($edit['label'] ?? '') ?>">
                <label for="label"><?= t('label') ?></label>
            </div>
            <div class="input-field col s4">
                <input type="number" name="seats" id="seats" value="<?= htmlspecialchars($edit['seats'] ?? '') ?>">
                <label for="seats"><?= t('seats') ?></label>
            </div>
            <div class="input-field col s4">
                <select name="status">
                    <option value="0" <?= isset($edit['status']) && $edit['status']==0 ? 'selected' : '' ?>><?= t('status_operationnal') ?></option>
                    <option value="1" <?= isset($edit['status']) && $edit['status']==1 ? 'selected' : '' ?>><?= t('status_maintenance') ?></option>
                    <option value="2" <?= isset($edit['status']) && $edit['status']==2 ? 'selected' : '' ?>><?= t('status_broken') ?></option>
                </select>
                <label><?= t('status') ?></label>
            </div>
        </div>
        <div class="row center-align">
            <button class="btn" type="submit"><?= t('save') ?></button>
        </div>
    </form>

    <p class="center-align" style="margin-top:20px;"><a href="dashboard.php" class="btn">Dashboard</a></p>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var elems = document.querySelectorAll('select');
            M.FormSelect.init(elems);
        });
    </script>
</body>
</html>
