<?php

function db_path(): string
{
    return dirname(__DIR__) . '/db/appcvlg_v3.sqlite';
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbFile = db_path();
    if (!is_dir(dirname($dbFile))) {
        mkdir(dirname($dbFile), 0777, true);
    }

    $isNew = !file_exists($dbFile) || filesize($dbFile) < 512;
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode = MEMORY');
    $pdo->exec('PRAGMA temp_store = MEMORY');
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew || !table_exists($pdo, 'Person')) {
        initialize_database($pdo);
    }

    return $pdo;
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table");
    $stmt->execute(['table' => $table]);

    return (bool) $stmt->fetchColumn();
}

function initialize_database(PDO $pdo): void
{
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schema);
    seed_database($pdo);
}

function seed_database(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM Person')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $pdo->beginTransaction();

    $addressStmt = $pdo->prepare(
        'INSERT INTO Address(street, streetNumber, postalCode, city, country) VALUES (?, ?, ?, ?, ?)'
    );
    $personStmt = $pdo->prepare(
        'INSERT INTO Person(firstName, lastName, nickname, email, username, password, mobile, address, language, role, partnerName)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $memberStmt = $pdo->prepare('INSERT INTO Member(person, type, canBook, partnerCode) VALUES (?, ?, ?, ?)');

    $addressStmt->execute(['Route de Bossey', '21', '1256', 'Troinex', 'Suisse']);
    $adminAddress = (int) $pdo->lastInsertId();
    $personStmt->execute([
        'Admin',
        'CVLG',
        'Superviseur',
        'admin@cvlg.local',
        'admin',
        password_hash('admin123', PASSWORD_DEFAULT),
        '+41220000000',
        $adminAddress,
        'fr',
        'R',
        null,
    ]);
    $adminPerson = (int) $pdo->lastInsertId();
    $memberStmt->execute([$adminPerson, 'actif', 1, null]);
    $adminMember = (int) $pdo->lastInsertId();

    $addressStmt->execute(['Chemin des Ailes', '5', '1201', 'Genève', 'Suisse']);
    $logAddress = (int) $pdo->lastInsertId();
    $personStmt->execute([
        'Luc',
        'Logistique',
        'Lulu',
        'logistique@cvlg.local',
        'logistique',
        password_hash('logistique123', PASSWORD_DEFAULT),
        '+41221111111',
        $logAddress,
        'fr',
        'L',
        null,
    ]);
    $logPerson = (int) $pdo->lastInsertId();
    $memberStmt->execute([$logPerson, 'actif', 1, null]);
    $logMember = (int) $pdo->lastInsertId();

    $addressStmt->execute(['Rue du Club', '8', '1203', 'Genève', 'Suisse']);
    $memberAddress = (int) $pdo->lastInsertId();
    $personStmt->execute([
        'Marie',
        'Membre',
        'Momo',
        'membre@cvlg.local',
        'membre',
        password_hash('membre123', PASSWORD_DEFAULT),
        '+41222222222',
        $memberAddress,
        'fr',
        'M',
        null,
    ]);
    $memberPerson = (int) $pdo->lastInsertId();
    $memberStmt->execute([$memberPerson, 'actif', 1, null]);
    $regularMember = (int) $pdo->lastInsertId();

    $personStmt->execute([
        'Ecole',
        'Partenaire',
        'Alti',
        'partenaire@cvlg.local',
        'partenaire',
        password_hash('partenaire123', PASSWORD_DEFAULT),
        '+41223333333',
        $memberAddress,
        'fr',
        'P',
        'Ecole Alti',
    ]);
    $partnerPerson = (int) $pdo->lastInsertId();
    $memberStmt->execute([$partnerPerson, 'partenaire', 1, 'ECOLE-ALTI']);

    $pdo->prepare('INSERT INTO Manager(person, rights) VALUES (?, ?)')->execute([$adminPerson, 4]);
    $pdo->prepare('INSERT INTO Manager(person, rights) VALUES (?, ?)')->execute([$logPerson, 2]);
    $pdo->prepare('INSERT INTO Driver(person, status) VALUES (?, ?)')->execute([$logPerson, 0]);

    $pdo->prepare(
        'INSERT INTO Vehicule(name, registration, label, seats, status) VALUES (?, ?, ?, ?, ?)'
    )->execute(['Navette principale', 'GE-2025', 'Minibus club', 8, 0]);
    $vehicleId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO License(person, label, number, validUntil) VALUES (?, ?, ?, ?)')
        ->execute([$memberPerson, 'Brevet pilote', 'FSVL-001', date('Y-12-31')]);

    $year = (int) date('Y');
    $yearFeeStmt = $pdo->prepare('INSERT INTO YearFee(year, type, price) VALUES (?, ?, ?)');
    $yearFeeStmt->execute([$year, 'actif', 120]);
    $activeFee = (int) $pdo->lastInsertId();
    $yearFeeStmt->execute([$year, 'honoraire', 0]);
    $yearFeeStmt->execute([$year, 'sympathisant', 30]);

    $dueStmt = $pdo->prepare('INSERT INTO MemberYearFee(member, yearFee, date, status, amount, paymentMethod) VALUES (?, ?, ?, ?, ?, ?)');
    $dueStmt->execute([$adminMember, $activeFee, date('Y-m-d'), 'paid', 120, 'cash']);
    $dueStmt->execute([$logMember, $activeFee, null, 'pending', 120, null]);
    $dueStmt->execute([$regularMember, $activeFee, null, 'pending', 120, null]);

    $pdo->prepare('INSERT INTO Ticket(person, quantity, price, date, used) VALUES (?, ?, ?, ?, ?)')
        ->execute([$memberPerson, 10, 90, date('Y-m-d'), 0]);

    $driverId = (int) $pdo->query('SELECT id FROM Driver WHERE person = ' . (int) $logPerson)->fetchColumn();
    $journeyStmt = $pdo->prepare(
        'INSERT INTO Journey(driver, vehicule, Label, kind, dateFrom, dateTo, timeStart, timeEnd, started, ended, createdBy, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $journeyStmt->execute([$driverId, $vehicleId, 'Matinale du Salève', 'club', $today, $today, '09:00', '10:00', 0, 0, $adminPerson, 'Créneau principal']);
    $journeyOne = (int) $pdo->lastInsertId();
    $journeyStmt->execute([$driverId, $vehicleId, 'Fin de journée', 'club', $tomorrow, $tomorrow, '17:30', '18:30', 0, 0, $logPerson, 'Créneau soir']);

    $memberIdForBooking = (int) $pdo->query('SELECT id FROM Member WHERE person = ' . (int) $memberPerson)->fetchColumn();
    $pdo->prepare(
        'INSERT INTO Booking(journey, member, status, guestName, qrCode) VALUES (?, ?, ?, ?, ?)'
    )->execute([$journeyOne, $memberIdForBooking, 'booked', null, strtoupper(bin2hex(random_bytes(4)))]);

    $settings = [
        'club_name' => 'Club de Vol Libre Geneve',
        'contact_email' => 'contact@cvlg.local',
        'ticket_price' => '9.00',
        'annual_fee_active' => '120',
        'annual_fee_supporter' => '30',
        'booking_window_days' => '3',
    ];

    $settingsStmt = $pdo->prepare('INSERT INTO Settings(key, value) VALUES (?, ?)');
    foreach ($settings as $key => $value) {
        $settingsStmt->execute([$key, $value]);
    }

    $pdo->commit();
}
