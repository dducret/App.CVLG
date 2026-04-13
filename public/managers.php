<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R']);
$rightsMap = [0 => 'Aucun', 1 => 'Lecture', 2 => 'Modification', 4 => 'Complet'];

if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM Manager WHERE id = ?')->execute([(int) $_GET['delete']]);
    flash('success', 'Manager supprime.');
    redirect('managers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person = (int) ($_POST['person'] ?? 0);
    $rights = (int) ($_POST['rights'] ?? 0);
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('UPDATE Manager SET person=?, rights=? WHERE id=?')->execute([$person, $rights, $id]);
    } else {
        db()->prepare('INSERT OR IGNORE INTO Manager(person, rights) VALUES(?, ?)')->execute([$person, $rights]);
    }
    flash('success', 'Manager enregistre.');
    redirect('managers.php');
}

$edit = isset($_GET['edit']) ? fetch_one('SELECT * FROM Manager WHERE id = ?', [(int) $_GET['edit']]) : null;
$managers = fetch_all('SELECT Manager.*, Person.firstName, Person.lastName FROM Manager INNER JOIN Person ON Person.id = Manager.person ORDER BY Person.lastName, Person.firstName');
$members = fetch_all('SELECT Member.person, Person.firstName || " " || Person.lastName AS name FROM Member INNER JOIN Person ON Person.id = Member.person ORDER BY Person.lastName, Person.firstName');

render_header('Gestion des managers', $user);
?>
<div class="row">
    <div class="col s12 l7">
        <div class="soft-box">
            <table class="striped">
                <thead><tr><th>Manager</th><th>Droits</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($managers as $manager): ?>
                    <tr>
                        <td><?= e($manager['firstName'] . ' ' . $manager['lastName']) ?></td>
                        <td><?= e($rightsMap[$manager['rights']] ?? (string) $manager['rights']) ?></td>
                        <td class="right-align">
                            <a class="btn-small" href="?edit=<?= (int) $manager['id'] ?>">Editer</a>
                            <a class="btn-small red" href="?delete=<?= (int) $manager['id'] ?>" onclick="return confirm('Supprimer ce manager ?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col s12 l5">
        <div class="soft-box">
            <h5><?= $edit ? 'Modifier un manager' : 'Ajouter un manager' ?></h5>
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
                    <select name="rights">
                        <?php foreach ($rightsMap as $key => $label): ?>
                            <option value="<?= $key ?>" <?= (int) ($edit['rights'] ?? 1) === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Droits</label>
                </div>
                <button class="btn" type="submit">Enregistrer</button>
            </form>
        </div>
    </div>
</div>
<?php render_footer(); ?>
