<?php

function stripe_is_enabled(): bool
{
    return setting_bool('stripe_enabled', false)
        && stripe_secret_key() !== ''
        && app_base_url() !== '';
}

function stripe_secret_key(): string
{
    return trim((string) setting('stripe_secret_key', ''));
}

function stripe_publishable_key(): string
{
    return trim((string) setting('stripe_publishable_key', ''));
}

function stripe_currency(): string
{
    return strtolower(trim((string) setting('stripe_currency', 'chf'))) ?: 'chf';
}

function app_base_url(): string
{
    $configured = trim((string) setting('app_base_url', ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    if (PHP_SAPI === 'cli') {
        return '';
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return '';
    }

    $scriptName = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    $basePath = rtrim($scriptName, '/.');

    return rtrim($scheme . '://' . $host . ($basePath !== '' ? $basePath : ''), '/');
}

function stripe_supports_checkout(): bool
{
    return stripe_is_enabled();
}

function stripe_minor_unit_multiplier(string $currency): int
{
    static $zeroDecimalCurrencies = [
        'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg',
        'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
    ];

    return in_array(strtolower($currency), $zeroDecimalCurrencies, true) ? 1 : 100;
}

function stripe_amount_to_minor_units(float $amount, ?string $currency = null): int
{
    $currency = $currency ?: stripe_currency();
    return (int) round($amount * stripe_minor_unit_multiplier($currency));
}

function stripe_amount_from_minor_units(int $amount, ?string $currency = null): float
{
    $currency = $currency ?: stripe_currency();
    return $amount / stripe_minor_unit_multiplier($currency);
}

function stripe_api_headers(): array
{
    $secretKey = stripe_secret_key();
    if ($secretKey === '') {
        throw new RuntimeException('Stripe n est pas configure.');
    }

    return [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/x-www-form-urlencoded',
    ];
}

function stripe_api_request(string $method, string $path, array $params = []): array
{
    $url = 'https://api.stripe.com' . $path;
    $payload = http_build_query($params);

    if (strtoupper($method) === 'GET' && $payload !== '') {
        $url .= '?' . $payload;
        $payload = '';
    }

    $responseBody = false;
    $statusCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => stripe_api_headers(),
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($payload !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            throw new RuntimeException('Impossible de contacter Stripe: ' . $curlError);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'header' => implode("\r\n", stripe_api_headers()),
                'content' => $payload,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);
        $responseBody = file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches)) {
            $statusCode = (int) $matches[1];
        }
    }

    $decoded = json_decode((string) $responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Reponse Stripe invalide.');
    }

    if ($statusCode >= 400) {
        $message = $decoded['error']['message'] ?? 'Erreur Stripe inconnue.';
        throw new RuntimeException($message);
    }

    return $decoded;
}

function stripe_create_payment(array $user, string $kind, string $description, float $amount, array $options = []): int
{
    $stmt = db()->prepare(
        'INSERT INTO Payment(person, memberYearFee, kind, description, quantity, unitAmount, amount, currency, provider, status, createdAt, updatedAt)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        (int) $user['id'],
        $options['member_year_fee_id'] ?? null,
        $kind,
        $description,
        $options['quantity'] ?? null,
        $options['unit_amount'] ?? null,
        $amount,
        strtolower((string) ($options['currency'] ?? stripe_currency())),
        'stripe',
        'pending',
        now_iso(),
        now_iso(),
    ]);

    return (int) db()->lastInsertId();
}

function stripe_update_payment_session(int $paymentId, array $session): void
{
    db()->prepare(
        'UPDATE Payment
         SET providerSessionId = ?, providerPayload = ?, updatedAt = ?
         WHERE id = ?'
    )->execute([
        $session['id'] ?? null,
        json_encode($session, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        now_iso(),
        $paymentId,
    ]);
}

function stripe_ticket_success_url(): string
{
    return app_base_url() . '/tickets.php?stripe_status=success&session_id={CHECKOUT_SESSION_ID}';
}

function stripe_ticket_cancel_url(): string
{
    return app_base_url() . '/tickets.php?stripe_status=cancel';
}

function stripe_due_success_url(): string
{
    return app_base_url() . '/my_dues.php?stripe_status=success&session_id={CHECKOUT_SESSION_ID}';
}

function stripe_due_cancel_url(): string
{
    return app_base_url() . '/my_dues.php?stripe_status=cancel';
}

function stripe_create_checkout_for_tickets(array $user, int $quantity, float $unitPrice): array
{
    $amount = $quantity * $unitPrice;
    $paymentId = stripe_create_payment(
        $user,
        'ticket',
        sprintf('Achat de %d ticket(s)', $quantity),
        $amount,
        [
            'quantity' => $quantity,
            'unit_amount' => $unitPrice,
        ]
    );

    $session = stripe_api_request('POST', '/v1/checkout/sessions', [
        'mode' => 'payment',
        'success_url' => stripe_ticket_success_url(),
        'cancel_url' => stripe_ticket_cancel_url(),
        'payment_method_types[0]' => 'card',
        'line_items[0][quantity]' => 1,
        'line_items[0][price_data][currency]' => stripe_currency(),
        'line_items[0][price_data][unit_amount]' => stripe_amount_to_minor_units($amount),
        'line_items[0][price_data][product_data][name]' => sprintf('Pack de %d ticket(s)', $quantity),
        'line_items[0][price_data][product_data][description]' => 'Tickets navette membres CVLG',
        'client_reference_id' => (string) $user['id'],
        'customer_email' => (string) ($user['email'] ?? ''),
        'metadata[payment_id]' => (string) $paymentId,
        'metadata[payment_kind]' => 'ticket',
        'metadata[person_id]' => (string) $user['id'],
        'metadata[quantity]' => (string) $quantity,
    ]);

    stripe_update_payment_session($paymentId, $session);

    return $session;
}

function stripe_create_checkout_for_due(array $user, array $due): array
{
    $amount = (float) $due['amount'];
    $paymentId = stripe_create_payment(
        $user,
        'membership_fee',
        sprintf('Cotisation %s', (string) $due['year']),
        $amount,
        [
            'member_year_fee_id' => (int) $due['id'],
        ]
    );

    $session = stripe_api_request('POST', '/v1/checkout/sessions', [
        'mode' => 'payment',
        'success_url' => stripe_due_success_url(),
        'cancel_url' => stripe_due_cancel_url(),
        'payment_method_types[0]' => 'card',
        'line_items[0][quantity]' => 1,
        'line_items[0][price_data][currency]' => stripe_currency(),
        'line_items[0][price_data][unit_amount]' => stripe_amount_to_minor_units($amount),
        'line_items[0][price_data][product_data][name]' => sprintf('Cotisation %s', (string) $due['year']),
        'line_items[0][price_data][product_data][description]' => 'Cotisation membre CVLG',
        'client_reference_id' => (string) $user['id'],
        'customer_email' => (string) ($user['email'] ?? ''),
        'metadata[payment_id]' => (string) $paymentId,
        'metadata[payment_kind]' => 'membership_fee',
        'metadata[person_id]' => (string) $user['id'],
        'metadata[member_year_fee_id]' => (string) $due['id'],
        'metadata[due_year]' => (string) $due['year'],
    ]);

    stripe_update_payment_session($paymentId, $session);

    return $session;
}

function stripe_find_payment_by_id(int $paymentId): ?array
{
    return fetch_one('SELECT * FROM Payment WHERE id = ?', [$paymentId]);
}

function stripe_find_payment_by_session_id(string $sessionId): ?array
{
    return fetch_one('SELECT * FROM Payment WHERE providerSessionId = ?', [$sessionId]);
}

function stripe_mark_payment(array $payment, array $session, string $status): void
{
    $paymentIntentId = is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : null;
    $chargeId = $session['charge'] ?? null;
    $receiptUrl = $session['receipt_url'] ?? null;
    $paidAt = $status === 'paid' ? now_iso() : null;

    db()->prepare(
        'UPDATE Payment
         SET status = ?, providerPaymentIntentId = COALESCE(?, providerPaymentIntentId),
             providerChargeId = COALESCE(?, providerChargeId), providerReceiptUrl = COALESCE(?, providerReceiptUrl),
             providerPayload = ?, paidAt = COALESCE(?, paidAt), updatedAt = ?
         WHERE id = ?'
    )->execute([
        $status,
        $paymentIntentId,
        $chargeId,
        $receiptUrl,
        json_encode($session, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $paidAt,
        now_iso(),
        (int) $payment['id'],
    ]);
}

function stripe_fulfill_payment(array $payment): void
{
    if (!empty($payment['fulfilledAt'])) {
        return;
    }

    $pdo = db();
    $pdo->beginTransaction();

    if ($payment['kind'] === 'ticket') {
        $pdo->prepare('INSERT INTO Ticket(person, quantity, price, date, used) VALUES (?, ?, ?, ?, 0)')
            ->execute([(int) $payment['person'], (int) $payment['quantity'], (float) $payment['amount'], today_iso()]);
    } elseif ($payment['kind'] === 'membership_fee' && !empty($payment['memberYearFee'])) {
        $pdo->prepare(
            "UPDATE MemberYearFee
             SET status = 'paid', date = ?, paymentMethod = ?
             WHERE id = ?"
        )->execute([today_iso(), 'stripe', (int) $payment['memberYearFee']]);
    }

    $pdo->prepare('UPDATE Payment SET fulfilledAt = ?, updatedAt = ? WHERE id = ?')
        ->execute([now_iso(), now_iso(), (int) $payment['id']]);
    $pdo->commit();
}

function stripe_sync_checkout_session(string $sessionId, ?int $expectedPersonId = null): array
{
    $session = stripe_api_request('GET', '/v1/checkout/sessions/' . rawurlencode($sessionId), [
        'expand[0]' => 'payment_intent.latest_charge',
        'expand[1]' => 'payment_intent.latest_charge.balance_transaction',
    ]);

    $paymentId = (int) ($session['metadata']['payment_id'] ?? 0);
    $payment = $paymentId > 0 ? stripe_find_payment_by_id($paymentId) : stripe_find_payment_by_session_id($sessionId);

    if (!$payment) {
        throw new RuntimeException('Paiement local introuvable pour cette session Stripe.');
    }

    if ($expectedPersonId !== null && (int) $payment['person'] !== $expectedPersonId) {
        throw new RuntimeException('Cette session Stripe ne correspond pas a votre compte.');
    }

    $charge = $session['payment_intent']['latest_charge'] ?? [];
    $session['charge'] = is_array($charge) ? ($charge['id'] ?? null) : null;
    $session['receipt_url'] = is_array($charge) ? ($charge['receipt_url'] ?? null) : null;

    $status = 'pending';
    if (($session['payment_status'] ?? '') === 'paid') {
        $status = 'paid';
    } elseif (($session['status'] ?? '') === 'expired') {
        $status = 'expired';
    }

    stripe_mark_payment($payment, $session, $status);
    $payment = stripe_find_payment_by_id((int) $payment['id']) ?: $payment;

    if ($status === 'paid') {
        stripe_fulfill_payment($payment);
        $payment = stripe_find_payment_by_id((int) $payment['id']) ?: $payment;
    }

    return [
        'payment' => $payment,
        'session' => $session,
        'status' => $status,
    ];
}

function stripe_sync_recent_sessions(int $limit = 25): array
{
    $response = stripe_api_request('GET', '/v1/checkout/sessions', ['limit' => $limit]);
    $sessions = $response['data'] ?? [];

    foreach ($sessions as $session) {
        $sessionId = $session['id'] ?? '';
        if ($sessionId === '') {
            continue;
        }

        $payment = stripe_find_payment_by_session_id($sessionId);
        if ($payment) {
            stripe_sync_checkout_session($sessionId);
        }
    }

    return is_array($sessions) ? $sessions : [];
}

function stripe_list_balance_transactions(int $limit = 25): array
{
    $response = stripe_api_request('GET', '/v1/balance_transactions', ['limit' => $limit]);
    return is_array($response['data'] ?? null) ? $response['data'] : [];
}
