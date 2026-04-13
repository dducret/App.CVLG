<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R', 'L', 'C']);
$pdo = db();

if (isset($_GET['delete'])) {
    $member = fetch_one('SELECT * FROM Member WHERE id = ?', [(int) $_GET['delete']]);
    if ($member) {
        $pdo->prepare('DELETE FROM Member WHERE id = ?')->execute([$member['id']]);
        $pdo->prepare('DELETE FROM Person WHERE id = ?')->execute([$member['person']]);
        flash('success', 'Membre supprime.');
    }
    redirect('members.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    $memberId = (int) ($_POST['member_id'] ?? 0);
    $personId = (int) ($_POST['person_id'] ?? 0);
    $addressId = null;
    $addressData = [
        trim($_POST['street'] ?? ''),
        trim($_POST['streetNumber'] ?? ''),
        trim($_POST['postalCode'] ?? ''),
        trim($_POST['city'] ?? ''),
        trim($_POST['country'] ?? ''),
    ];

    if ($memberId > 0) {
        $existing = fetch_one('SELECT * FROM Person WHERE id = ?', [$personId]);
        if ($existing && !empty($existing['address'])) {
            $addressId = (int) $existing['address'];
            $pdo->prepare('UPDATE Address SET street=?, streetNumber=?, postalCode=?, city=?, country=? WHERE id=?')
                ->execute([...$addressData, $addressId]);
        }
    }

    if (!$addressId) {
        $pdo->prepare('INSERT INTO Address(street, streetNumber, postalCode, city, country) VALUES (?, ?, ?, ?, ?)')
            ->execute($addressData);
        $addressId = (int) $pdo->lastInsertId();
    }

    $password = trim($_POST['password'] ?? '');
    if ($memberId > 0) {
        $pdo->prepare(
            'UPDATE Person SET firstName=?, lastName=?, nickname=?, email=?, username=?, mobile=?, address=?, language=?, role=? WHERE id=?'
        )->execute([
            trim($_POST['firstName']),
            trim($_POST['lastName']),
            trim($_POST['nickname']),
            trim($_POST['email']),
            trim($_POST['username']),
            trim($_POST['mobile']),
            $addressId,
            trim($_POST['language'] ?? 'fr'),
            trim($_POST['role'] ?? 'M'),
            $personId,
        ]);
        if ($password !== '') {
            $pdo->prepare('UPDATE Person SET password=? WHERE id=?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $personId]);
        }
        $pdo->prepare('UPDATE Member SET type=?, canBook=? WHERE id=?')
            ->execute([$_POST['type'] ?? 'actif', isset($_POST['canBook']) ? 1 : 0, $memberId]);
        flash('success', 'Membre mis a jour.');
    } else {
        $pdo->prepare(
            'INSERT INTO Person(firstName, lastName, nickname, email, username, password, mobile, address, language, role)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            trim($_POST['firstName']),
            trim($_POST['lastName']),
            trim($_POST['nickname']),
            trim($_POST['email']),
            trim($_POST['username']),
            password_hash($password !== '' ? $password : 'change-me', PASSWORD_DEFAULT),
            trim($_POST['mobile']),
            $addressId,
            trim($_POST['language'] ?? 'fr'),
            trim($_POST['role'] ?? 'M'),
        ]);
        $personId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO Member(person, type, canBook) VALUES (?, ?, ?)')
            ->execute([$personId, $_POST['type'] ?? 'actif', isset($_POST['canBook']) ? 1 : 0]);
        flash('success', 'Membre cree.');
    }

    if (trim($_POST['license_label'] ?? '') !== '') {
        $pdo->prepare('INSERT INTO License(person, label, number, validUntil) VALUES (?, ?, ?, ?)')
            ->execute([
                $personId,
                trim($_POST['license_label']),
                trim($_POST['license_number'] ?? ''),
                trim($_POST['license_valid_until'] ?? ''),
            ]);
    }

    $pdo->commit();
    save_journal($personId, 'Edition membre');
    redirect('members.php');
}

$search = trim($_GET['q'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$conditions = [];
$params = [];
if ($search !== '') {
    $conditions[] = '(Person.firstName LIKE ? OR Person.lastName LIKE ? OR Person.nickname LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($typeFilter !== '') {
    $conditions[] = 'Member.type = ?';
    $params[] = $typeFilter;
}

$sql = 'SELECT Member.id AS member_id, Member.type, Member.canBook, Person.id AS person_id, Person.firstName, Person.lastName, Person.nickname,
        Person.email, Person.username, Person.mobile, Person.role, Person.language, Address.street, Address.streetNumber, Address.postalCode,
        Address.city, Address.country
        FROM Member
        INNER JOIN Person ON Person.id = Member.person
        LEFT JOIN Address ON Address.id = Person.address';
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY Person.lastName, Person.firstName';
$members = fetch_all($sql, $params);

$edit = isset($_GET['edit']) ? fetch_one(
    'SELECT Member.id AS member_id, Member.type, Member.canBook, Person.id AS person_id, Person.firstName, Person.lastName, Person.nickname,
     Person.email, Person.username, Person.mobile, Person.role, Person.language, Address.street, Address.streetNumber, Address.postalCode,
     Address.city, Address.country
     FROM Member
     INNER JOIN Person ON Person.id = Member.person
     LEFT JOIN Address ON Address.id = Person.address
     WHERE Member.id = ?',
    [(int) $_GET['edit']]
) : null;

render_header('Gestion des membres', $user);
?>
<div class="row">
    <div class="col s12 l8">
        <div class="soft-box">
            <form method="get" class="row">
                <div class="input-field col s12 m6">
                    <input type="text" name="q" id="q" value="<?= e($search) ?>">
                    <label for="q" class="active">Recherche</label>
                </div>
                <div class="input-field col s12 m4">
                    <select name="type">
                        <option value="">Tous</option>
                        <?php foreach (['actif', 'honoraire', 'sympathisant', 'partenaire'] as $type): ?>
                            <option value="<?= e($type) ?>" <?= $typeFilter === $type ? 'selected' : '' ?>><?= e(ucfirst($type)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Type</label>
                </div>
                <div class="col s12 m2" style="padding-top: 18px;">
                    <button class="btn">Filtrer</button>
                </div>
            </form>
            <table class="striped">
                <thead><tr><th>Membre</th><th>Email</th><th>Type</th><th>Role</th><th>Ville</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= e($member['firstName'] . ' ' . $member['lastName']) ?></td>
                        <td><?= e($member['email']) ?></td>
                        <td><?= e($member['type']) ?></td>
                        <td><?= e($member['role']) ?></td>
                        <td><?= e($member['city']) ?></td>
                        <td class="right-align">
                            <a class="btn-small" href="?edit=<?= (int) $member['member_id'] ?>">Editer</a>
                            <a class="btn-small red" href="?delete=<?= (int) $member['member_id'] ?>" onclick="return confirm('Supprimer ce membre ?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col s12 l4">
        <div class="soft-box">
            <h5><?= $edit ? 'Modifier un membre' : 'Nouveau membre' ?></h5>
            <form method="post">
                <input type="hidden" name="member_id" value="<?= e($edit['member_id'] ?? '') ?>">
                <input type="hidden" name="person_id" value="<?= e($edit['person_id'] ?? '') ?>">
                <div class="input-field"><input type="text" id="firstName" name="firstName" value="<?= e($edit['firstName'] ?? '') ?>" required><label for="firstName" class="active">Prenom</label></div>
                <div class="input-field"><input type="text" id="lastName" name="lastName" value="<?= e($edit['lastName'] ?? '') ?>" required><label for="lastName" class="active">Nom</label></div>
                <div class="input-field"><input type="text" id="nickname" name="nickname" value="<?= e($edit['nickname'] ?? '') ?>"><label for="nickname" class="active">Surnom</label></div>
                <div class="input-field"><input type="email" id="email" name="email" value="<?= e($edit['email'] ?? '') ?>" required><label for="email" class="active">Email</label></div>
                <div class="input-field"><input type="text" id="username" name="username" value="<?= e($edit['username'] ?? '') ?>"><label for="username" class="active">Identifiant</label></div>
                <div class="input-field"><input type="password" id="password" name="password"><label for="password">Mot de passe <?= $edit ? '(laisser vide pour conserver)' : '' ?></label></div>
                <div class="input-field"><input type="text" id="mobile" name="mobile" value="<?= e($edit['mobile'] ?? '') ?>"><label for="mobile" class="active">Mobile</label></div>
                <div class="input-field">
                    <?php $currentType = $edit['type'] ?? 'actif'; ?>
                    <select name="type">
                        <?php foreach (['actif', 'honoraire', 'sympathisant', 'partenaire'] as $type): ?>
                            <option value="<?= e($type) ?>" <?= $currentType === $type ? 'selected' : '' ?>><?= e(ucfirst($type)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Type de membre</label>
                </div>
                <div class="input-field">
                    <?php $currentRole = $edit['role'] ?? 'M'; ?>
                    <select name="role">
                        <?php foreach (['R', 'L', 'P', 'M', 'H', 'K', 'C', 'G', 'X'] as $role): ?>
                            <option value="<?= e($role) ?>" <?= $currentRole === $role ? 'selected' : '' ?>><?= e($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Role</label>
                </div>
                <p><label><input type="checkbox" name="canBook" <?= !empty($edit['canBook']) || !$edit ? 'checked' : '' ?>><span>Peut reserver</span></label></p>
                <div class="input-field"><input type="text" id="street" name="street" value="<?= e($edit['street'] ?? '') ?>"><label for="street" class="active">Rue</label></div>
                <div class="input-field"><input type="text" id="streetNumber" name="streetNumber" value="<?= e($edit['streetNumber'] ?? '') ?>"><label for="streetNumber" class="active">Numero</label></div>
                <div class="input-field"><input type="text" id="postalCode" name="postalCode" value="<?= e($edit['postalCode'] ?? '') ?>"><label for="postalCode" class="active">NPA</label></div>
                <div class="input-field"><input type="text" id="city" name="city" value="<?= e($edit['city'] ?? '') ?>"><label for="city" class="active">Ville</label></div>
                <div class="input-field"><input type="text" id="country" name="country" value="<?= e($edit['country'] ?? 'Suisse') ?>"><label for="country" class="active">Pays</label></div>
                <div class="input-field"><input type="text" id="license_label" name="license_label"><label for="license_label">Nouvelle licence</label></div>
                <div class="input-field"><input type="text" id="license_number" name="license_number"><label for="license_number">Numero de licence</label></div>
                <div class="input-field"><input type="date" id="license_valid_until" name="license_valid_until"><label for="license_valid_until" class="active">Valable jusqu'au</label></div>
                <button class="btn" type="submit">Enregistrer</button>
            </form>
        </div>
    </div>
</div>
<?php render_footer(); ?>
