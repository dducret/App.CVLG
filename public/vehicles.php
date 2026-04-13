<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R', 'L']);
$statusLabels = [0 => 'Operationnel', 1 => 'En maintenance', 2 => 'En panne'];

if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM Vehicule WHERE id = ?')->execute([(int) $_GET['delete']]);
    flash('success', 'Vehicule supprime.');
    redirect('vehicles.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        trim($_POST['name'] ?? ''),
        trim($_POST['registration'] ?? ''),
        trim($_POST['label'] ?? ''),
        (int) ($_POST['seats'] ?? 0),
        (int) ($_POST['status'] ?? 0),
    ];
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('UPDATE Vehicule SET name=?, registration=?, label=?, seats=?, status=? WHERE id=?')
            ->execute([...$payload, $id]);
        flash('success', 'Vehicule mis a jour.');
    } else {
        db()->prepare('INSERT INTO Vehicule(name, registration, label, seats, status) VALUES (?, ?, ?, ?, ?)')
            ->execute($payload);
        flash('success', 'Vehicule ajoute.');
    }
    redirect('vehicles.php');
}

$edit = isset($_GET['edit']) ? fetch_one('SELECT * FROM Vehicule WHERE id = ?', [(int) $_GET['edit']]) : null;
$vehicles = fetch_all('SELECT * FROM Vehicule ORDER BY name');

render_header('Gestion des vehicules', $user);
?>
<div class="row">
    <div class="col s12 l7">
        <div class="soft-box">
            <table class="striped">
                <thead><tr><th>Nom</th><th>Immatriculation</th><th>Places</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                        <td><?= e($vehicle['name']) ?></td>
                        <td><?= e($vehicle['registration']) ?></td>
                        <td><?= e((string) $vehicle['seats']) ?></td>
                        <td><?= e($statusLabels[$vehicle['status']] ?? (string) $vehicle['status']) ?></td>
                        <td class="right-align">
                            <a class="btn-small" href="?edit=<?= (int) $vehicle['id'] ?>">Editer</a>
                            <a class="btn-small red" href="?delete=<?= (int) $vehicle['id'] ?>" onclick="return confirm('Supprimer ce vehicule ?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col s12 l5">
        <div class="soft-box">
            <h5><?= $edit ? 'Modifier un vehicule' : 'Nouveau vehicule' ?></h5>
            <form method="post">
                <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
                <div class="input-field"><input type="text" id="name" name="name" value="<?= e($edit['name'] ?? '') ?>" required><label for="name" class="active">Nom</label></div>
                <div class="input-field"><input type="text" id="registration" name="registration" value="<?= e($edit['registration'] ?? '') ?>"><label for="registration" class="active">Immatriculation</label></div>
                <div class="input-field"><input type="text" id="label" name="label" value="<?= e($edit['label'] ?? '') ?>"><label for="label" class="active">Libelle</label></div>
                <div class="input-field"><input type="number" id="seats" name="seats" value="<?= e($edit['seats'] ?? '8') ?>"><label for="seats" class="active">Places</label></div>
                <div class="input-field">
                    <select name="status">
                        <?php foreach ($statusLabels as $key => $label): ?>
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
