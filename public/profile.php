<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $addressId = !empty($user['address']) ? (int) $user['address'] : 0;
    if ($addressId > 0) {
        $pdo->prepare('UPDATE Address SET street=?, streetNumber=?, postalCode=?, city=?, country=? WHERE id=?')
            ->execute([
                trim($_POST['street'] ?? ''),
                trim($_POST['streetNumber'] ?? ''),
                trim($_POST['postalCode'] ?? ''),
                trim($_POST['city'] ?? ''),
                trim($_POST['country'] ?? ''),
                $addressId,
            ]);
    }

    $pdo->prepare('UPDATE Person SET firstName=?, lastName=?, nickname=?, mobile=?, birthday=?, gender=?, nationality=?, language=? WHERE id=?')
        ->execute([
            trim($_POST['firstName'] ?? ''),
            trim($_POST['lastName'] ?? ''),
            trim($_POST['nickname'] ?? ''),
            trim($_POST['mobile'] ?? ''),
            trim($_POST['birthday'] ?? ''),
            trim($_POST['gender'] ?? ''),
            trim($_POST['nationality'] ?? ''),
            trim($_POST['language'] ?? 'fr'),
            (int) $user['id'],
        ]);

    $password = trim($_POST['password'] ?? '');
    if ($password !== '') {
        $pdo->prepare('UPDATE Person SET password=? WHERE id=?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
    }

    flash('success', 'Profil mis a jour.');
    redirect('profile.php');
}

$user = current_user(true);
$licenses = fetch_all('SELECT * FROM License WHERE person = ? ORDER BY validUntil DESC', [(int) $user['id']]);
render_header('Mon profil', $user);
?>
<div class="row">
    <div class="col s12 l7">
        <div class="soft-box">
            <form method="post">
                <div class="input-field"><input type="text" id="firstName" name="firstName" value="<?= e($user['firstName']) ?>"><label for="firstName" class="active">Prenom</label></div>
                <div class="input-field"><input type="text" id="lastName" name="lastName" value="<?= e($user['lastName']) ?>"><label for="lastName" class="active">Nom</label></div>
                <div class="input-field"><input type="text" id="nickname" name="nickname" value="<?= e($user['nickname']) ?>"><label for="nickname" class="active">Surnom</label></div>
                <div class="input-field"><input type="text" id="mobile" name="mobile" value="<?= e($user['mobile']) ?>"><label for="mobile" class="active">Mobile</label></div>
                <div class="input-field"><input type="date" id="birthday" name="birthday" value="<?= e($user['birthday']) ?>"><label for="birthday" class="active">Date de naissance</label></div>
                <div class="input-field"><input type="text" id="gender" name="gender" value="<?= e($user['gender']) ?>"><label for="gender" class="active">Sexe</label></div>
                <div class="input-field"><input type="text" id="nationality" name="nationality" value="<?= e($user['nationality']) ?>"><label for="nationality" class="active">Nationalite</label></div>
                <div class="input-field"><input type="text" id="street" name="street" value="<?= e($user['street'] ?? '') ?>"><label for="street" class="active">Rue</label></div>
                <div class="input-field"><input type="text" id="streetNumber" name="streetNumber" value="<?= e($user['streetNumber'] ?? '') ?>"><label for="streetNumber" class="active">Numero</label></div>
                <div class="input-field"><input type="text" id="postalCode" name="postalCode" value="<?= e($user['postalCode'] ?? '') ?>"><label for="postalCode" class="active">NPA</label></div>
                <div class="input-field"><input type="text" id="city" name="city" value="<?= e($user['city'] ?? '') ?>"><label for="city" class="active">Ville</label></div>
                <div class="input-field"><input type="text" id="country" name="country" value="<?= e($user['country'] ?? '') ?>"><label for="country" class="active">Pays</label></div>
                <div class="input-field"><input type="text" id="language" name="language" value="<?= e($user['language']) ?>"><label for="language" class="active">Langue</label></div>
                <div class="input-field"><input type="password" id="password" name="password"><label for="password">Nouveau mot de passe</label></div>
                <button class="btn" type="submit">Enregistrer</button>
            </form>
        </div>
    </div>
    <div class="col s12 l5">
        <div class="soft-box">
            <h5>Licences</h5>
            <ul class="collection">
                <?php foreach ($licenses as $license): ?>
                    <li class="collection-item"><?= e($license['label']) ?> - <?= e($license['number']) ?> - <?= e(format_date($license['validUntil'])) ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Email de connexion: <strong><?= e($user['email']) ?></strong></p>
            <p>Role: <strong><?= e($user['role']) ?></strong></p>
            <p>Type de membre: <strong><?= e($user['memberType'] ?? '-') ?></strong></p>
        </div>
    </div>
</div>
<?php render_footer(); ?>
