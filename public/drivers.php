<?php
session_start();
require __DIR__ . '/lang.php';
require __DIR__ . '/api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$statusMap = [
    0 => t('available'),
    1 => t('rest'),
    2 => t('off_duty')
];

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM Driver WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    header('Location: drivers.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person = (int)($_POST['person'] ?? 0);
    $status = (int)($_POST['status'] ?? 0);
    $id = $_POST['id'] ?? null;
    $check = $pdo->prepare('SELECT COUNT(*) FROM Member WHERE person = ?');
    $check->execute([$person]);
    if ($check->fetchColumn() > 0) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE Driver SET person = ?, status = ? WHERE id = ?');
            $stmt->execute([$person, $status, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO Driver(person,status) VALUES (?,?)');
            $stmt->execute([$person, $status]);
        }
    }
    header('Location: drivers.php');
    exit;
}

$sql = 'SELECT Driver.id, Driver.status, Driver.person, Person.firstName, Person.lastName
        FROM Driver JOIN Person ON Person.id = Driver.person';
$drivers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$members = $pdo->query('SELECT Member.person, Person.firstName || " " || Person.lastName AS name
                         FROM Member JOIN Person ON Person.id = Member.person')->fetchAll(PDO::FETCH_ASSOC);

$editDriver = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM Driver WHERE id = ?');
    $stmt->execute([$_GET['edit']]);
    $editDriver = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= t('driver_management') ?></title>
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
<h3 class="center-align"><?= t('driver_management') ?></h3>

<table class="striped">
    <thead>
    <tr>
        <th><?= t('driver_member') ?></th>
        <th><?= t('driver_status') ?></th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($drivers as $d): ?>
        <tr>
            <td><?= htmlspecialchars($d['firstName'] . ' ' . $d['lastName']) ?></td>
            <td><?= htmlspecialchars($statusMap[$d['status']] ?? $d['status']) ?></td>
            <td>
                <a href="?edit=<?= $d['id'] ?>" class="btn-small"><?= t('edit') ?></a>
                <a href="?delete=<?= $d['id'] ?>" class="btn-small red" onclick="return confirm('Delete?')">
                    <?= t('delete') ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h4><?= $editDriver ? t('update_driver') : t('add_driver') ?></h4>
<form method="post">
    <?php if ($editDriver): ?>
        <input type="hidden" name="id" value="<?= $editDriver['id'] ?>">
    <?php endif; ?>
    <div class="row">
        <div class="input-field col s6">
            <select name="person" required>
                <?php foreach ($members as $m): ?>
                    <option value="<?= $m['person'] ?>" <?= ($editDriver && $editDriver['person'] == $m['person']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label><?= t('driver_member') ?></label>
        </div>
        <div class="input-field col s6">
            <select name="status">
                <?php foreach ($statusMap as $k => $v): ?>
                    <option value="<?= $k ?>" <?= ($editDriver && $editDriver['status'] == $k) ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
            <label><?= t('driver_status') ?></label>
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
