<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, ?string $message = null): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        return null;
    }

    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

function current_user(bool $refresh = false): ?array
{
    static $user = false;

    if (!$refresh && is_array($user)) {
        return $user;
    }

    if (empty($_SESSION['user_id'])) {
        $user = null;
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT
            Person.*,
            Address.city,
            Address.country,
            Member.id AS memberId,
            Member.type AS memberType,
            Member.canBook,
            Driver.id AS driverId,
            Driver.status AS driverStatus,
            Manager.id AS managerId,
            Manager.rights AS managerRights
         FROM Person
         LEFT JOIN Address ON Address.id = Person.address
         LEFT JOIN Member ON Member.person = Person.id
         LEFT JOIN Driver ON Driver.person = Person.id
         LEFT JOIN Manager ON Manager.person = Person.id
         WHERE Person.id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Veuillez vous connecter.');
        redirect('index.php');
    }

    return $user;
}

function require_roles(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        flash('error', 'Acces refuse.');
        redirect('dashboard.php');
    }

    return $user;
}

function is_admin_like(array $user): bool
{
    return in_array($user['role'], ['R', 'L', 'C'], true);
}

function can_manage_journeys(array $user): bool
{
    return in_array($user['role'], ['R', 'L', 'P', 'C'], true) || !empty($user['driverId']);
}

function can_manage_communications(array $user): bool
{
    return in_array($user['role'], ['R'], true);
}

function now_iso(): string
{
    return date('Y-m-d H:i:s');
}

function today_iso(): string
{
    return date('Y-m-d');
}

function fetch_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function fetch_value(string $sql, array $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function setting(string $key, ?string $default = null): ?string
{
    $value = fetch_value('SELECT value FROM Settings WHERE key = ?', [$key]);
    return $value !== false ? (string) $value : $default;
}

function format_money($amount): string
{
    return number_format((float) $amount, 2, '.', ' ') . ' CHF';
}

function format_date(?string $date): string
{
    if (!$date) {
        return '-';
    }

    return date('d.m.Y', strtotime($date));
}

function remaining_tickets(int $personId): int
{
    $bought = (int) fetch_value('SELECT COALESCE(SUM(quantity), 0) FROM Ticket WHERE person = ?', [$personId]);
    $reserved = (int) fetch_value(
        "SELECT COUNT(*)
         FROM Booking
         INNER JOIN Member ON Member.id = Booking.member
         WHERE Member.person = ?
           AND Booking.disable = 0
           AND Booking.status IN ('booked', 'validated', 'waitlist')",
        [$personId]
    );

    return max(0, $bought - $reserved);
}

function member_can_book(array $user): bool
{
    if (empty($user['memberId']) || (int) $user['canBook'] !== 1) {
        return false;
    }

    if (in_array($user['role'], ['K', 'X', 'G'], true)) {
        return false;
    }

    $pendingDue = (int) fetch_value(
        "SELECT COUNT(*)
         FROM MemberYearFee
         WHERE member = ?
           AND status != 'paid'",
        [$user['memberId']]
    );

    return $pendingDue === 0;
}

function journey_capacity(int $journeyId): int
{
    $capacity = fetch_value(
        'SELECT COALESCE(Vehicule.seats, 0)
         FROM Journey
         LEFT JOIN Vehicule ON Vehicule.id = Journey.vehicule
         WHERE Journey.id = ?',
        [$journeyId]
    );

    return (int) $capacity;
}

function journey_reserved_count(int $journeyId): int
{
    return (int) fetch_value(
        "SELECT COUNT(*)
         FROM Booking
         WHERE journey = ?
           AND disable = 0
           AND status IN ('booked', 'validated')",
        [$journeyId]
    );
}

function save_journal(?int $personId, string $label): void
{
    db()->prepare('INSERT INTO Journal(person, label) VALUES(?, ?)')->execute([$personId, $label]);
}
