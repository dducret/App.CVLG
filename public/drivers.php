<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R', 'L']);
$statusMap = [0 => 'Disponible', 1 => 'Repos', 2 => 'Hors service'];

if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM Driver WHERE id = ?')->execute([(int) $_GET['delete']]);
    flash('success', 'Chauffeur supprime.');
    redirect('drivers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person = (int) ($_POST['person'] ?? 0);
    $status = (int) ($_POST['status'] ?? 0);
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('UPDATE Driver SET person = ?, status = ? WHERE id = ?')->execute([$person, $status, $id]);
    } else {
        db()->prepare('INSERT OR IGNORE INTO Driver(person, status) VALUES(?, ?)')->execute([$person, $status]);
    }
    flash('success', 'Chauffeur enregistre.');
    redirect('drivers.php');
}

$drivers = fetch_all('SELECT Driver.id, Driver.status, Driver.person, Person.firstName, Person.lastName FROM Driver INNER JOIN Person ON Person.id = Driver.person ORDER BY Person.lastName, Person.firstName');
$members = fetch_all('SELECT Member.person, Person.firstName || " " || Person.lastName AS name FROM Member INNER JOIN Person ON Person.id = Member.person ORDER BY Person.lastName, Person.firstName');
$edit = isset($_GET['edit']) ? fetch_one('SELECT * FROM Driver WHERE id = ?', [(int) $_GET['edit']]) : null;

render_header('Gestion des chauffeurs', $user);
?>
<div class="row">
    <div class="col s12 l7">
        <div class="soft-box">
            <table class="striped">
                <thead><tr><th>Chauffeur</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($drivers as $driver): ?>
                    <tr>
                        <td><?= e($driver['firstName'] . ' ' . $driver['lastName']) ?></td>
                        <td><?= e($statusMap[$driver['status']] ?? (string) $driver['status']) ?></td>
                        <td class="right-align">
                            <a class="btn-small" href="?edit=<?= (int) $driver['id'] ?>">Editer</a>
                            <a class="btn-small red" href="?delete=<?= (int) $driver['id'] ?>" onclick="return confirm('Supprimer ce chauffeur ?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col s12 l5">
        <div class="soft-box">
            <h5><?= $edit ? 'Modifier le chauffeur' : 'Ajouter un chauffeur' ?></h5>
            <form method="post">
                <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
                <div class="input-field">
                    <select name="person">
                        <?php foreach ($members as $member): ?>
                            <option value="<?= (int) $member['person'] ?>" <?= (int) ($edit['person'] ?? 0) === (int) $member['person'] ? 'selected' : '' ?>><?= e($member['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Membre</label>
                </div>
                <div class="input-field">
                    <select name="status">
                        <?php foreach ($statusMap as $key => $label): ?>
                            <option value="<?= $key ?>" <?= (int) ($edit['status'] ?? 0) === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Statut</label>
                </div>
                <button class="btn" type="submit">Enregistrer</button>
            </form>
        </div>
    </div>
</div>
<?php render_footer(); ?>
