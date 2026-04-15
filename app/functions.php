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

function materialize_version(): string
{
    return '1.0.0';
}

function materialize_cdn_base(): string
{
    return 'https://cdnjs.cloudflare.com/ajax/libs/materialize/' . materialize_version();
}

function render_materialize_css(): void
{
    ?>
    <link rel="stylesheet" href="<?= e(materialize_cdn_base()) ?>/css/materialize.min.css">
    <?php
}

function render_materialize_js(array $components = ['FormSelect']): void
{
    ?>
<script src="<?= e(materialize_cdn_base()) ?>/js/materialize.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
<?php foreach ($components as $component): ?>
    <?php if ($component === 'FormSelect'): ?>
    M.FormSelect.init(document.querySelectorAll('select'));
    <?php elseif ($component === 'Tabs'): ?>
    M.Tabs.init(document.querySelectorAll('.tabs'));
    <?php elseif ($component === 'Modal'): ?>
    M.Modal.init(document.querySelectorAll('.modal'));
    <?php elseif ($component === 'Sidenav'): ?>
    M.Sidenav.init(document.querySelectorAll('.sidenav'));
    <?php endif; ?>
<?php endforeach; ?>
});
</script>
    <?php
}

function render_flash_message(?array $flash): void
{
    if (!$flash) {
        return;
    }
    ?>
    <div class="card-panel <?= $flash['type'] === 'error' ? 'red lighten-4 red-text text-darken-3' : 'green lighten-4 green-text text-darken-3' ?>">
        <?= e($flash['message']) ?>
    </div>
    <?php
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
        flash('error', t('flash_login_required', 'Veuillez vous connecter.'));
        redirect('index.php');
    }

    if (!empty($user['language'])) {
        set_lang($user['language']);
    }

    return $user;
}

function require_roles(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        flash('error', t('flash_access_denied', 'Acces refuse.'));
        redirect('dashboard.php');
    }

    return $user;
}

function landing_page_for_user(array $user): string
{
    return is_admin_like($user) || can_manage_journeys($user) ? 'dashboard.php' : 'bookings.php';
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

function setting_int(string $key, int $default): int
{
    return (int) setting($key, (string) $default);
}

function setting_bool(string $key, bool $default = false): bool
{
    return setting($key, $default ? '1' : '0') === '1';
}

function translate_status(?string $status): string
{
    $normalized = strtolower(trim((string) $status));
    if ($normalized === '') {
        return '-';
    }

    $key = 'status_' . preg_replace('/[^a-z0-9]+/', '_', $normalized);
    return t($key, (string) $status);
}

function booking_rule_definitions(): array
{
    return [
        'booking_rule_same_time_block' => [
            'label' => 'Bloquer deux réservations à la même heure',
            'type' => 'boolean',
            'default' => '1',
            'help' => 'Un membre ne peut pas être inscrit sur deux navettes ayant la même date et la même heure.',
        ],
        'booking_rule_daily_confirmed_limit' => [
            'label' => 'Nombre maximal de réservations fermes par jour',
            'type' => 'number',
            'default' => '1',
            'help' => 'Au-delà de cette limite, une nouvelle demande passe en liste d’attente.',
        ],
        'booking_rule_allow_waitlist_after_daily_limit' => [
            'label' => 'Autoriser la liste d’attente après la limite de réservation ferme',
            'type' => 'boolean',
            'default' => '1',
            'help' => 'Si activé, une autre navette le même jour reste possible mais uniquement en attente.',
        ],
        'booking_rule_journey_waitlist_limit' => [
            'label' => 'Taille maximale de la liste d’attente par navette',
            'type' => 'number',
            'default' => '3',
            'help' => 'Nombre maximal de membres en attente sur une navette.',
        ],
        'booking_rule_daily_waitlist_limit' => [
            'label' => 'Nombre maximal de navettes en attente par membre et par jour',
            'type' => 'number',
            'default' => '3',
            'help' => 'Limite le nombre de navettes en liste d’attente sur une même journée.',
        ],
    ];
}

function format_money($amount, string $currency = 'CHF'): string
{
    return number_format((float) $amount, 2, '.', ' ') . ' ' . strtoupper($currency);
}

function format_date(?string $date): string
{
    if (!$date) {
        return '-';
    }

    return date('d.m.Y', strtotime($date));
}

function format_datetime(?string $date): string
{
    if (!$date) {
        return '-';
    }

    return date('d.m.Y H:i', strtotime($date));
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

function booking_requested_seats(?string $guestName): int
{
    return trim((string) $guestName) !== '' ? 2 : 1;
}

function booking_active_statuses(): array
{
    return ['booked', 'validated', 'waitlist'];
}

function booking_same_time_conflict_count(int $memberId, string $dateFrom, string $timeStart, int $journeyId): int
{
    return (int) fetch_value(
        "SELECT COUNT(*)
         FROM Booking
         INNER JOIN Journey ON Journey.id = Booking.journey
         WHERE Booking.member = ?
           AND Booking.disable = 0
           AND Booking.status IN ('booked', 'validated', 'waitlist')
           AND Journey.dateFrom = ?
           AND Journey.timeStart = ?
           AND Journey.id != ?",
        [$memberId, $dateFrom, $timeStart, $journeyId]
    );
}

function booking_existing_for_journey(int $memberId, int $journeyId): int
{
    return (int) fetch_value(
        "SELECT COUNT(*)
         FROM Booking
         WHERE member = ?
           AND journey = ?
           AND disable = 0
           AND status IN ('booked', 'validated', 'waitlist')",
        [$memberId, $journeyId]
    );
}

function booking_confirmed_count_for_day(int $memberId, string $dateFrom): int
{
    return (int) fetch_value(
        "SELECT COUNT(*)
         FROM Booking
         INNER JOIN Journey ON Journey.id = Booking.journey
         WHERE Booking.member = ?
           AND Booking.disable = 0
           AND Booking.status IN ('booked', 'validated')
           AND Journey.dateFrom = ?",
        [$memberId, $dateFrom]
    );
}

function booking_waitlist_count_for_journey(int $journeyId): int
{
    return (int) fetch_value(
        "SELECT COUNT(*)
         FROM Booking
         WHERE journey = ?
           AND disable = 0
           AND status = 'waitlist'",
        [$journeyId]
    );
}

function booking_waitlist_count_for_day(int $memberId, string $dateFrom): int
{
    return (int) fetch_value(
        "SELECT COUNT(*)
         FROM Booking
         INNER JOIN Journey ON Journey.id = Booking.journey
         WHERE Booking.member = ?
           AND Booking.disable = 0
           AND Booking.status = 'waitlist'
           AND Journey.dateFrom = ?",
        [$memberId, $dateFrom]
    );
}

function evaluate_booking_request(int $memberId, int $journeyId, ?string $guestName = null): array
{
    $journey = fetch_one(
        'SELECT Journey.id, Journey.Label, Journey.dateFrom, Journey.timeStart
         FROM Journey
         WHERE Journey.id = ?',
        [$journeyId]
    );
    if (!$journey) {
        return [
            'allowed' => false,
            'status' => null,
            'message' => 'Navette introuvable.',
        ];
    }

    if (booking_existing_for_journey($memberId, $journeyId) > 0) {
        return [
            'allowed' => false,
            'status' => null,
            'message' => 'Une reservation existe deja pour cette navette.',
        ];
    }

    if (booking_same_time_conflict_count($memberId, $journey['dateFrom'], $journey['timeStart'], $journeyId) > 0
        && setting_bool('booking_rule_same_time_block', true)
    ) {
        return [
            'allowed' => false,
            'status' => null,
            'message' => 'Une reservation existe deja pour cette meme heure.',
        ];
    }

    $requestedSeats = booking_requested_seats($guestName);
    $capacity = journey_capacity($journeyId);
    $reservedSeats = journey_reserved_count($journeyId);
    $status = $reservedSeats + $requestedSeats > $capacity ? 'waitlist' : 'booked';

    $dailyConfirmedLimit = max(0, setting_int('booking_rule_daily_confirmed_limit', 1));
    $allowWaitlistAfterDailyLimit = setting_bool('booking_rule_allow_waitlist_after_daily_limit', true);
    $confirmedForDay = booking_confirmed_count_for_day($memberId, $journey['dateFrom']);

    if ($status === 'booked' && $dailyConfirmedLimit > 0 && $confirmedForDay >= $dailyConfirmedLimit) {
        if (!$allowWaitlistAfterDailyLimit) {
            return [
                'allowed' => false,
                'status' => null,
                'message' => 'La limite de reservations fermes pour cette journee est atteinte.',
            ];
        }
        $status = 'waitlist';
    }

    if ($status === 'waitlist') {
        $journeyWaitlistLimit = max(0, setting_int('booking_rule_journey_waitlist_limit', 3));
        if ($journeyWaitlistLimit > 0 && booking_waitlist_count_for_journey($journeyId) >= $journeyWaitlistLimit) {
            return [
                'allowed' => false,
                'status' => null,
                'message' => 'La liste d attente de cette navette est complete.',
            ];
        }

        $dailyWaitlistLimit = max(0, setting_int('booking_rule_daily_waitlist_limit', 3));
        if ($dailyWaitlistLimit > 0 && booking_waitlist_count_for_day($memberId, $journey['dateFrom']) >= $dailyWaitlistLimit) {
            return [
                'allowed' => false,
                'status' => null,
                'message' => 'La limite de navettes en attente pour cette journee est atteinte.',
            ];
        }
    }

    return [
        'allowed' => true,
        'status' => $status,
        'message' => $status === 'waitlist'
            ? 'Reservation placee en liste d attente.'
            : 'Reservation confirmee.',
    ];
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
        "SELECT COALESCE(SUM(CASE WHEN COALESCE(TRIM(guestName), '') = '' THEN 1 ELSE 2 END), 0)
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
