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

    ensure_schema_updates($pdo);
    ensure_demo_data($pdo);

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

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->query(sprintf('PRAGMA table_info("%s")', str_replace('"', '""', $table)));
    $columns = $stmt ? $stmt->fetchAll() : [];

    foreach ($columns as $info) {
        if (($info['name'] ?? null) === $column) {
            return true;
        }
    }

    return false;
}

function ensure_schema_updates(PDO $pdo): void
{
    $messageColumns = [
        'audience' => "ALTER TABLE Message ADD COLUMN audience TEXT DEFAULT 'all'",
        'extraRecipients' => "ALTER TABLE Message ADD COLUMN extraRecipients TEXT",
        'smtpError' => "ALTER TABLE Message ADD COLUMN smtpError TEXT",
        'recipientEmails' => "ALTER TABLE Message ADD COLUMN recipientEmails TEXT",
        'updatedAt' => "ALTER TABLE Message ADD COLUMN updatedAt TEXT",
    ];

    foreach ($messageColumns as $column => $sql) {
        if (!column_exists($pdo, 'Message', $column)) {
            $pdo->exec($sql);
        }
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS MessageAttachment (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            message INTEGER NOT NULL,
            originalName TEXT NOT NULL,
            storedName TEXT NOT NULL,
            mimeType TEXT,
            size INTEGER NOT NULL DEFAULT 0,
            createdAt TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(message) REFERENCES Message(id) ON DELETE CASCADE
        )'
    );
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
        'booking_rule_same_time_block' => '1',
        'booking_rule_daily_confirmed_limit' => '1',
        'booking_rule_allow_waitlist_after_daily_limit' => '1',
        'booking_rule_journey_waitlist_limit' => '3',
        'booking_rule_daily_waitlist_limit' => '3',
    ];

    $settingsStmt = $pdo->prepare('INSERT INTO Settings(key, value) VALUES (?, ?)');
    foreach ($settings as $key => $value) {
        $settingsStmt->execute([$key, $value]);
    }

    $pdo->commit();
}

function ensure_demo_data(PDO $pdo): void
{
    ensure_default_settings($pdo);
    ensure_demo_member_can_book($pdo);
    [$driverId, $vehicleId] = ensure_demo_driver_and_vehicle($pdo);
    ensure_demo_april_journeys($pdo, $driverId, $vehicleId);
}

function ensure_default_settings(PDO $pdo): void
{
    $defaults = [
        'booking_rule_same_time_block' => '1',
        'booking_rule_daily_confirmed_limit' => '1',
        'booking_rule_allow_waitlist_after_daily_limit' => '1',
        'booking_rule_journey_waitlist_limit' => '3',
        'booking_rule_daily_waitlist_limit' => '3',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_email' => '',
        'smtp_from_name' => 'CVLG',
        'smtp_reply_to' => '',
    ];
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO Settings(key, value) VALUES (?, ?)');
    foreach ($defaults as $key => $value) {
        $stmt->execute([$key, $value]);
    }
}

function ensure_demo_member_can_book(PDO $pdo): void
{
    $memberId = (int) $pdo->query(
        "SELECT Member.id
         FROM Member
         INNER JOIN Person ON Person.id = Member.person
         WHERE Person.email = 'membre@cvlg.local'
         LIMIT 1"
    )->fetchColumn();

    if ($memberId <= 0) {
        return;
    }

    $currentYear = (int) date('Y');
    $stmt = $pdo->prepare('SELECT id FROM YearFee WHERE year = ? AND type = ? LIMIT 1');
    $stmt->execute([$currentYear, 'actif']);
    $yearFeeId = (int) $stmt->fetchColumn();

    if ($yearFeeId <= 0) {
        $pdo->prepare('INSERT INTO YearFee(year, type, price) VALUES (?, ?, ?)')->execute([$currentYear, 'actif', 120]);
        $yearFeeId = (int) $pdo->lastInsertId();
    }

    $existingDue = fetch_one('SELECT id FROM MemberYearFee WHERE member = ? AND yearFee = ?', [$memberId, $yearFeeId]);
    if ($existingDue) {
        $pdo->prepare("UPDATE MemberYearFee SET status = 'paid', date = COALESCE(date, ?), amount = CASE WHEN amount = 0 THEN 120 ELSE amount END, paymentMethod = COALESCE(paymentMethod, 'demo') WHERE id = ?")
            ->execute([date('Y-m-d'), (int) $existingDue['id']]);
    } else {
        $pdo->prepare("INSERT INTO MemberYearFee(member, yearFee, date, status, amount, paymentMethod) VALUES (?, ?, ?, 'paid', ?, 'demo')")
            ->execute([$memberId, $yearFeeId, date('Y-m-d'), 120]);
    }

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(Ticket.quantity), 0)
         FROM Ticket
         INNER JOIN Person ON Person.id = Ticket.person
         WHERE Person.email = 'membre@cvlg.local'"
    );
    $stmt->execute();
    $ticketCount = (int) $stmt->fetchColumn();

    if ($ticketCount <= 0) {
        $personId = (int) $pdo->query("SELECT id FROM Person WHERE email = 'membre@cvlg.local' LIMIT 1")->fetchColumn();
        if ($personId > 0) {
            $pdo->prepare('INSERT INTO Ticket(person, quantity, price, date, used) VALUES (?, ?, ?, ?, 0)')
                ->execute([$personId, 10, 90, date('Y-m-d')]);
        }
    }
}

function ensure_demo_driver_and_vehicle(PDO $pdo): array
{
    $driverId = (int) $pdo->query(
        "SELECT Driver.id
         FROM Driver
         INNER JOIN Person ON Person.id = Driver.person
         WHERE Person.email = 'logistique@cvlg.local'
         LIMIT 1"
    )->fetchColumn();

    if ($driverId <= 0) {
        $personId = (int) $pdo->query("SELECT id FROM Person WHERE email = 'logistique@cvlg.local' LIMIT 1")->fetchColumn();
        if ($personId > 0) {
            $pdo->prepare('INSERT INTO Driver(person, status) VALUES (?, 0)')->execute([$personId]);
            $driverId = (int) $pdo->lastInsertId();
        }
    }

    $vehicleId = (int) $pdo->query("SELECT id FROM Vehicule WHERE name = 'Navette principale' LIMIT 1")->fetchColumn();
    if ($vehicleId <= 0) {
        $pdo->prepare('INSERT INTO Vehicule(name, registration, label, seats, status) VALUES (?, ?, ?, ?, ?)')
            ->execute(['Navette principale', 'GE-2025', 'Minibus club', 8, 0]);
        $vehicleId = (int) $pdo->lastInsertId();
    }

    return [$driverId, $vehicleId];
}

function ensure_demo_april_journeys(PDO $pdo, int $driverId, int $vehicleId): void
{
    if ($driverId <= 0 || $vehicleId <= 0) {
        return;
    }

    $year = (int) date('Y');
    $slots = [
        ['Navette 13h30', '13:30', '14:00'],
        ['Navette 14h30', '14:30', '15:00'],
        ['Navette 15h30', '15:30', '16:00'],
        ['Navette 15h30 bis', '15:30', '16:15'],
        ['Navette 17h30', '17:30', '18:00'],
    ];
    $allowedWeekdays = [3, 5, 6, 7];
    $creatorId = (int) $pdo->query("SELECT id FROM Person WHERE email = 'admin@cvlg.local' LIMIT 1")->fetchColumn();
    $insertStmt = $pdo->prepare(
        'INSERT INTO Journey(driver, vehicule, Label, kind, dateFrom, dateTo, timeStart, timeEnd, createdBy, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $existsStmt = $pdo->prepare(
        'SELECT id FROM Journey WHERE dateFrom = ? AND timeStart = ? AND Label = ? LIMIT 1'
    );

    $cursor = new DateTimeImmutable(sprintf('%d-04-01', $year));
    $end = new DateTimeImmutable(sprintf('%d-04-30', $year));

    while ($cursor <= $end) {
        $weekday = (int) $cursor->format('N');
        if (in_array($weekday, $allowedWeekdays, true)) {
            foreach ($slots as $index => [$label, $timeStart, $timeEnd]) {
                $date = $cursor->format('Y-m-d');
                $existsStmt->execute([$date, $timeStart, $label]);
                if (!$existsStmt->fetchColumn()) {
                    $insertStmt->execute([
                        $driverId,
                        $vehicleId,
                        $label,
                        'club',
                        $date,
                        $date,
                        $timeStart,
                        $timeEnd,
                        $creatorId ?: null,
                        $index === 2 ? 'Rotation reguliere club' : 'Navette de demonstration',
                    ]);
                }
            }
        }
        $cursor = $cursor->modify('+1 day');
    }
}
