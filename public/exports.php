<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R']);
$pdo = db();
$defaultDateTo = today_iso();
$defaultDateFrom = date('Y-m-d', strtotime('-30 days'));
$selectedExportType = $_POST['export_type'] ?? 'members';
$paymentDateFrom = trim((string) ($_POST['payment_date_from'] ?? $defaultDateFrom));
$paymentDateTo = trim((string) ($_POST['payment_date_to'] ?? $defaultDateTo));
$importSummary = null;

function csv_header_key(string $value): string
{
    $value = strtolower(trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    return preg_replace('/[^a-z0-9]+/', '', $value) ?: '';
}

function csv_field(array $row, array $map, array $names, string $default = ''): string
{
    foreach ($names as $name) {
        $key = csv_header_key($name);
        if ($key !== '' && isset($map[$key])) {
            $index = $map[$key];
            return isset($row[$index]) ? trim((string) $row[$index]) : $default;
        }
    }

    return $default;
}

function csv_bool_field(array $row, array $map, array $names, bool $default = true): int
{
    $value = strtolower(csv_field($row, $map, $names, $default ? '1' : '0'));
    if ($value === '') {
        return $default ? 1 : 0;
    }

    return in_array($value, ['1', 'true', 'yes', 'oui', 'y', 'on'], true) ? 1 : 0;
}

function open_csv_upload(string $path)
{
    $sample = file($path, FILE_IGNORE_NEW_LINES);
    $firstLine = $sample[0] ?? '';
    $semicolonCount = substr_count($firstLine, ';');
    $commaCount = substr_count($firstLine, ',');
    $delimiter = $semicolonCount > $commaCount ? ';' : ',';

    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException('Impossible de lire le fichier CSV.');
    }

    return [$handle, $delimiter];
}

function import_members_from_csv(PDO $pdo, string $path): array
{
    [$handle, $delimiter] = open_csv_upload($path);
    $header = fgetcsv($handle, 0, $delimiter);
    if (!$header) {
        fclose($handle);
        throw new RuntimeException('Le CSV est vide.');
    }

    $headerMap = [];
    foreach ($header as $index => $column) {
        $normalized = csv_header_key((string) $column);
        if ($normalized !== '') {
            $headerMap[$normalized] = $index;
        }
    }

    $requiredColumns = ['firstname', 'lastname', 'email'];
    foreach ($requiredColumns as $requiredColumn) {
        if (!isset($headerMap[$requiredColumn])) {
            fclose($handle);
            throw new RuntimeException('Colonnes requises: firstName/prenom, lastName/nom, email.');
        }
    }

    $summary = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    $lineNumber = 1;
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $lineNumber++;
        if ($row === [null] || count(array_filter($row, static fn ($value) => trim((string) $value) !== '')) === 0) {
            continue;
        }

        $firstName = csv_field($row, $headerMap, ['firstName', 'prenom']);
        $lastName = csv_field($row, $headerMap, ['lastName', 'nom']);
        $email = strtolower(csv_field($row, $headerMap, ['email']));

        if ($firstName === '' || $lastName === '' || $email === '') {
            $summary['skipped']++;
            $summary['errors'][] = 'Ligne ' . $lineNumber . ': prenom, nom ou email manquant.';
            continue;
        }

        $person = fetch_one('SELECT * FROM Person WHERE email = ?', [$email]);
        $addressData = [
            csv_field($row, $headerMap, ['street', 'rue']),
            csv_field($row, $headerMap, ['streetNumber', 'numero']),
            csv_field($row, $headerMap, ['postalCode', 'npa']),
            csv_field($row, $headerMap, ['city', 'ville']),
            csv_field($row, $headerMap, ['country', 'pays'], 'Suisse'),
        ];

        try {
            if ($person) {
                $personId = (int) $person['id'];
                $addressId = !empty($person['address']) ? (int) $person['address'] : 0;

                if ($addressId > 0) {
                    $pdo->prepare('UPDATE Address SET street = ?, streetNumber = ?, postalCode = ?, city = ?, country = ? WHERE id = ?')
                        ->execute([...$addressData, $addressId]);
                } else {
                    $pdo->prepare('INSERT INTO Address(street, streetNumber, postalCode, city, country) VALUES (?, ?, ?, ?, ?)')
                        ->execute($addressData);
                    $addressId = (int) $pdo->lastInsertId();
                }

                $updateParams = [
                    $firstName,
                    $lastName,
                    csv_field($row, $headerMap, ['nickname', 'surnom']),
                    $email,
                    csv_field($row, $headerMap, ['username', 'identifiant'], (string) ($person['username'] ?? '')),
                    csv_field($row, $headerMap, ['mobile', 'phone', 'telephone', 'tel'], (string) ($person['mobile'] ?? '')),
                    $addressId,
                    csv_field($row, $headerMap, ['language', 'langue'], (string) ($person['language'] ?? 'fr')),
                    csv_field($row, $headerMap, ['role'], (string) ($person['role'] ?? 'M')),
                    $personId,
                ];

                $pdo->prepare(
                    'UPDATE Person SET firstName = ?, lastName = ?, nickname = ?, email = ?, username = ?, mobile = ?, address = ?, language = ?, role = ? WHERE id = ?'
                )->execute($updateParams);

                $password = csv_field($row, $headerMap, ['password', 'motdepasse']);
                if ($password !== '') {
                    $pdo->prepare('UPDATE Person SET password = ? WHERE id = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $personId]);
                }

                $member = fetch_one('SELECT * FROM Member WHERE person = ?', [$personId]);
                if ($member) {
                    $pdo->prepare('UPDATE Member SET type = ?, canBook = ? WHERE person = ?')
                        ->execute([
                            csv_field($row, $headerMap, ['type'], (string) ($member['type'] ?? 'actif')),
                            csv_bool_field($row, $headerMap, ['canBook', 'peutreserver'], (int) ($member['canBook'] ?? 1) === 1),
                            $personId,
                        ]);
                } else {
                    $pdo->prepare('INSERT INTO Member(person, type, canBook) VALUES (?, ?, ?)')
                        ->execute([
                            $personId,
                            csv_field($row, $headerMap, ['type'], 'actif'),
                            csv_bool_field($row, $headerMap, ['canBook', 'peutreserver'], true),
                        ]);
                }

                $summary['updated']++;
            } else {
                $pdo->prepare('INSERT INTO Address(street, streetNumber, postalCode, city, country) VALUES (?, ?, ?, ?, ?)')
                    ->execute($addressData);
                $addressId = (int) $pdo->lastInsertId();

                $password = csv_field($row, $headerMap, ['password', 'motdepasse'], 'change-me');
                $pdo->prepare(
                    'INSERT INTO Person(firstName, lastName, nickname, email, username, password, mobile, address, language, role)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $firstName,
                    $lastName,
                    csv_field($row, $headerMap, ['nickname', 'surnom']),
                    $email,
                    csv_field($row, $headerMap, ['username', 'identifiant'], strstr($email, '@', true) ?: $email),
                    password_hash($password, PASSWORD_DEFAULT),
                    csv_field($row, $headerMap, ['mobile', 'phone', 'telephone', 'tel']),
                    $addressId,
                    csv_field($row, $headerMap, ['language', 'langue'], 'fr'),
                    csv_field($row, $headerMap, ['role'], 'M'),
                ]);

                $personId = (int) $pdo->lastInsertId();
                $pdo->prepare('INSERT INTO Member(person, type, canBook) VALUES (?, ?, ?)')
                    ->execute([
                        $personId,
                        csv_field($row, $headerMap, ['type'], 'actif'),
                        csv_bool_field($row, $headerMap, ['canBook', 'peutreserver'], true),
                    ]);

                $summary['created']++;
            }
        } catch (Throwable $exception) {
            $summary['skipped']++;
            $summary['errors'][] = 'Ligne ' . $lineNumber . ': ' . $exception->getMessage();
        }
    }

    fclose($handle);
    return $summary;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'export';

    if ($action === 'backup') {
        $backupSource = db_path();
        if (!is_file($backupSource)) {
            flash('error', 'Base de donnees introuvable.');
            redirect('exports.php');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="cvlg-backup-' . date('Ymd-His') . '.sqlite"');
        header('Content-Length: ' . (string) filesize($backupSource));
        readfile($backupSource);
        exit;
    }

    if ($action === 'import_members') {
        if (!isset($_FILES['members_csv']) || (int) ($_FILES['members_csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Veuillez selectionner un fichier CSV valide.');
            redirect('exports.php');
        }

        $pdo->beginTransaction();
        try {
            $importSummary = import_members_from_csv($pdo, (string) $_FILES['members_csv']['tmp_name']);
            $pdo->commit();
            save_journal((int) $user['id'], 'Import CSV membres');
            flash(
                'success',
                sprintf(
                    'Import termine. %d crees, %d mis a jour, %d ignores.',
                    $importSummary['created'],
                    $importSummary['updated'],
                    $importSummary['skipped']
                )
            );
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'Import CSV impossible: ' . $exception->getMessage());
        }
        $_SESSION['import_summary'] = $importSummary;
        redirect('exports.php');
    }

    $type = $_POST['export_type'] ?? 'members';
    $filename = 'cvlg-' . $type . '-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    if ($type === 'members') {
        fputcsv($out, ['firstName', 'lastName', 'email', 'type', 'role']);
        foreach (fetch_all('SELECT Person.firstName, Person.lastName, Person.email, Member.type, Person.role FROM Member INNER JOIN Person ON Person.id = Member.person ORDER BY Person.lastName') as $row) {
            fputcsv($out, $row);
        }
    } elseif ($type === 'dues') {
        fputcsv($out, ['member', 'year', 'status', 'amount', 'paymentMethod']);
        foreach (fetch_all('SELECT Person.firstName || " " || Person.lastName AS member, YearFee.year, MemberYearFee.status, MemberYearFee.amount, MemberYearFee.paymentMethod FROM MemberYearFee INNER JOIN Member ON Member.id = MemberYearFee.member INNER JOIN Person ON Person.id = Member.person INNER JOIN YearFee ON YearFee.id = MemberYearFee.yearFee ORDER BY YearFee.year DESC') as $row) {
            fputcsv($out, $row);
        }
    } elseif ($type === 'bookings') {
        fputcsv($out, ['journey', 'date', 'member', 'status', 'guestName']);
        foreach (fetch_all('SELECT Journey.Label, Journey.dateFrom, Person.firstName || " " || Person.lastName AS member, Booking.status, Booking.guestName FROM Booking INNER JOIN Journey ON Journey.id = Booking.journey INNER JOIN Member ON Member.id = Booking.member INNER JOIN Person ON Person.id = Member.person ORDER BY Journey.dateFrom DESC') as $row) {
            fputcsv($out, $row);
        }
    } elseif ($type === 'payments') {
        $conditions = [];
        $params = [];
        if ($paymentDateFrom !== '') {
            $conditions[] = 'date(COALESCE(Payment.paidAt, Payment.createdAt)) >= ?';
            $params[] = $paymentDateFrom;
        }
        if ($paymentDateTo !== '') {
            $conditions[] = 'date(COALESCE(Payment.paidAt, Payment.createdAt)) <= ?';
            $params[] = $paymentDateTo;
        }

        $sql = "SELECT
                    Person.firstName || ' ' || Person.lastName AS member,
                    Person.email,
                    Payment.kind,
                    Payment.description,
                    Payment.status,
                    Payment.amount,
                    Payment.currency,
                    Payment.provider,
                    Payment.createdAt,
                    Payment.paidAt
                FROM Payment
                INNER JOIN Person ON Person.id = Payment.person";
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY COALESCE(Payment.paidAt, Payment.createdAt) DESC, Payment.id DESC';

        fputcsv($out, ['member', 'email', 'why', 'description', 'status', 'amount', 'currency', 'provider', 'createdAt', 'paidAt']);
        foreach (fetch_all($sql, $params) as $row) {
            fputcsv($out, $row);
        }
    } else {
        fputcsv($out, ['person', 'quantity', 'price', 'date']);
        foreach (fetch_all('SELECT Person.firstName || " " || Person.lastName AS person, Ticket.quantity, Ticket.price, Ticket.date FROM Ticket INNER JOIN Person ON Person.id = Ticket.person ORDER BY Ticket.date DESC') as $row) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit;
}

$importSummary = $_SESSION['import_summary'] ?? null;
unset($_SESSION['import_summary']);

render_header('Exports CSV', $user);
?>
<div class="row">
    <div class="col s12 l6">
        <div class="soft-box">
            <h5>Exports</h5>
            <form method="post" id="exports-form">
                <input type="hidden" name="action" value="export">
                <div class="input-field">
                    <select name="export_type" id="export_type">
                        <option value="members" <?= $selectedExportType === 'members' ? 'selected' : '' ?>>Membres</option>
                        <option value="dues" <?= $selectedExportType === 'dues' ? 'selected' : '' ?>>Cotisations</option>
                        <option value="bookings" <?= $selectedExportType === 'bookings' ? 'selected' : '' ?>>Réservations</option>
                        <option value="payments" <?= $selectedExportType === 'payments' ? 'selected' : '' ?>>Paiements</option>
                    </select>
                    <label>Type d'export</label>
                </div>
                <div class="row" id="payment-date-filters" style="<?= $selectedExportType === 'payments' ? '' : 'display: none;' ?>">
                    <div class="input-field col s12 m6">
                        <input type="date" id="payment_date_from" name="payment_date_from" value="<?= e($paymentDateFrom) ?>">
                        <label for="payment_date_from" class="active">Date depart paiements</label>
                    </div>
                    <div class="input-field col s12 m6">
                        <input type="date" id="payment_date_to" name="payment_date_to" value="<?= e($paymentDateTo) ?>">
                        <label for="payment_date_to" class="active">Date arrivee paiements</label>
                    </div>
                </div>
                <p class="grey-text text-darken-1">Pour l’export paiements, le CSV inclut qui, pourquoi, quand, montant et statut. Le filtre s’applique sur la date de paiement ou, a defaut, la date de creation.</p>
                <button class="btn" type="submit">Generer le CSV</button>
            </form>
        </div>
    </div>
    <div class="col s12 l6">
        <div class="soft-box">
            <h5>Backup base de donnees</h5>
            <p>Telecharge un backup complet de la base SQLite actuelle.</p>
            <form method="post">
                <input type="hidden" name="action" value="backup">
                <button class="btn" type="submit">Telecharger le backup</button>
            </form>
        </div>
        <div class="soft-box">
            <h5>Import CSV membres</h5>
            <p>Colonnes attendues: <code>firstName</code>, <code>lastName</code>, <code>email</code>. Colonnes optionnelles: <code>type</code>, <code>role</code>, <code>language</code>, <code>mobile</code>, <code>street</code>, <code>streetNumber</code>, <code>postalCode</code>, <code>city</code>, <code>country</code>, <code>username</code>, <code>nickname</code>, <code>canBook</code>, <code>password</code>. Les entetes francaises simples sont aussi acceptees.</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_members">
                <div class="file-field input-field">
                    <div class="btn">
                        <span>Choisir CSV</span>
                        <input type="file" name="members_csv" accept=".csv,text/csv" required>
                    </div>
                    <div class="file-path-wrapper">
                        <input class="file-path validate" type="text" placeholder="Importer un fichier CSV de membres">
                    </div>
                </div>
                <button class="btn" type="submit">Importer les membres</button>
            </form>
            <?php if (is_array($importSummary) && !empty($importSummary['errors'])): ?>
                <div class="card-panel amber lighten-4" style="margin-top: 16px;">
                    <strong>Lignes ignorees</strong>
                    <ul class="browser-default" style="margin-top: 8px;">
                        <?php foreach (array_slice($importSummary['errors'], 0, 10) as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var exportTypeField = document.getElementById('export_type');
    var paymentFilters = document.getElementById('payment-date-filters');
    if (!exportTypeField || !paymentFilters) {
        return;
    }

    var syncPaymentFilters = function () {
        paymentFilters.style.display = exportTypeField.value === 'payments' ? '' : 'none';
    };

    exportTypeField.addEventListener('change', syncPaymentFilters);
    syncPaymentFilters();
});
</script>
<?php render_footer(); ?>
