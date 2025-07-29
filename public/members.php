<?php
session_start();
require __DIR__ . '/lang.php';
require __DIR__ . '/api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Handle form submission to add member
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO Address(street, streetNumber, postalCode, city, country) VALUES (?,?,?,?,?)');
    $stmt->execute([
        $_POST['street'] ?? '',
        $_POST['streetNumber'] ?? '',
        $_POST['postalCode'] ?? '',
        $_POST['city'] ?? '',
        $_POST['country'] ?? ''
    ]);
    $addressId = $pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO Person(firstName,lastName,email,password,mobile,address,language) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([
        $_POST['firstName'] ?? '',
        $_POST['lastName'] ?? '',
        $_POST['email'] ?? '',
        password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
        $_POST['mobile'] ?? '',
        $addressId,
        $_POST['language'] ?? 'en'
    ]);
    $personId = $pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO Member(person,type) VALUES (?,?)');
    $stmt->execute([
        $personId,
        $_POST['type'] ?? 'actif'
    ]);
    $pdo->commit();
}

// Fetch members
$sql = 'SELECT Member.id as id, Member.type, Person.firstName, Person.lastName, Person.email, Person.mobile, Address.street, Address.streetNumber, Address.postalCode, Address.city, Address.country
        FROM Member
        JOIN Person ON Person.id = Member.person
        LEFT JOIN Address ON Address.id = Person.address';
$members = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= t('member_management') ?></title>
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
    <h3 class="center-align"><?= t('member_management') ?></h3>

    <table class="striped">
        <thead>
            <tr>
                <th><?= t('first_name') ?></th>
                <th><?= t('last_name') ?></th>
                <th><?= t('email') ?></th>
                <th><?= t('member_type') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['firstName']) ?></td>
                    <td><?= htmlspecialchars($m['lastName']) ?></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><?= htmlspecialchars($m['type']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4><?= t('add_member') ?></h4>
    <form method="post">
        <div class="row">
            <div class="input-field col s6">
                <input type="text" name="firstName" id="firstName" required>
                <label for="firstName"><?= t('first_name') ?></label>
            </div>
            <div class="input-field col s6">
                <input type="text" name="lastName" id="lastName" required>
                <label for="lastName"><?= t('last_name') ?></label>
            </div>
        </div>
        <div class="row">
            <div class="input-field col s6">
                <input type="email" name="email" id="email" required>
                <label for="email"><?= t('email') ?></label>
            </div>
            <div class="input-field col s6">
                <input type="password" name="password" id="password" required>
                <label for="password"><?= t('password') ?></label>
            </div>
        </div>
        <div class="row">
            <div class="input-field col s6">
                <input type="text" name="mobile" id="mobile">
                <label for="mobile">Mobile</label>
            </div>
            <div class="input-field col s6">
                <select name="type">
                    <option value="actif">actif</option>
                    <option value="passif">passif</option>
                    <option value="permanent">permanent</option>
                </select>
                <label><?= t('member_type') ?></label>
            </div>
        </div>
        <div class="row">
            <div class="input-field col s6">
                <input type="text" name="street" id="street">
                <label for="street"><?= t('street') ?></label>
            </div>
            <div class="input-field col s6">
                <input type="text" name="streetNumber" id="streetNumber">
                <label for="streetNumber"><?= t('street_number') ?></label>
            </div>
        </div>
        <div class="row">
            <div class="input-field col s4">
                <input type="text" name="postalCode" id="postalCode">
                <label for="postalCode"><?= t('postal_code') ?></label>
            </div>
            <div class="input-field col s4">
                <input type="text" name="city" id="city">
                <label for="city"><?= t('city') ?></label>
            </div>
            <div class="input-field col s4">
                <input type="text" name="country" id="country">
                <label for="country"><?= t('country') ?></label>
            </div>
        </div>
        <div class="row">
            <div class="input-field col s12">
                <select name="language">
                    <option value="en">English</option>
                    <option value="fr">Français</option>
                    <option value="de">Deutsch</option>
                    <option value="it">Italiano</option>
                </select>
                <label><?= t('language') ?></label>
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
