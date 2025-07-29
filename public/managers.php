<?php
session_start();
require __DIR__ . '/lang.php';
require __DIR__ . '/api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$rightsMap = [
    0 => t('right_read'),
    1 => t('right_modify'),
    2 => t('right_full')
];

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM Manager WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    header('Location: managers.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM Manager WHERE id = ?');
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person = (int)($_POST['person'] ?? 0);
    $rights = (int)($_POST['rights'] ?? 0);
    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare('UPDATE Manager SET person=?, rights=? WHERE id=?');
        $stmt->execute([$person, $rights, $_POST['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO Manager(person, rights) VALUES(?,?)');
        $stmt->execute([$person, $rights]);
    }
    header('Location: managers.php');
    exit;
}

$sql = 'SELECT Manager.id, Manager.person, Manager.rights, Person.firstName, Person.lastName
        FROM Manager JOIN Person ON Person.id = Manager.person';
$managers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$members = $pdo->query('SELECT Member.person, Person.firstName || " " || Person.lastName AS name
                         FROM Member JOIN Person ON Person.id = Member.person')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= t('manager_management') ?></title>
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
<h3 class="center-align"><?= t('manager_management') ?></h3>

<table class="striped">
    <thead>
    <tr>
        <th><?= t('manager_member') ?></th>
        <th><?= t('manager_rights') ?></th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($managers as $m): ?>
        <tr>
            <td><?= htmlspecialchars($m['firstName'] . ' ' . $m['lastName']) ?></td>
            <td><?= htmlspecialchars($rightsMap[$m['rights']] ?? $m['rights']) ?></td>
            <td>
                <a href="?edit=<?= $m['id'] ?>" class="btn-small"><?= t('edit') ?></a>
                <a href="?delete=<?= $m['id'] ?>" class="btn-small red" onclick="return confirm('Delete?')">
                    <?= t('delete') ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h4><?= $edit ? t('update_manager') : t('add_manager') ?></h4>
<form method="post">
    <?php if ($edit): ?>
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <?php endif; ?>
    <div class="row">
        <div class="input-field col s6">
            <select name="person" required>
                <?php foreach ($members as $mem): ?>
                    <option value="<?= $mem['person'] ?>" <?= ($edit && $edit['person'] == $mem['person']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mem['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label><?= t('manager_member') ?></label>
        </div>
        <div class="input-field col s6">
            <select name="rights">
                <?php foreach ($rightsMap as $k => $v): ?>
                    <option value="<?= $k ?>" <?= ($edit && $edit['rights'] == $k) ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
            <label><?= t('manager_rights') ?></label>
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
