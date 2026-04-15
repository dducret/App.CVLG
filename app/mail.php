<?php

function communication_audience_options(): array
{
    return [
        'all' => 'Tous les membres',
        'drivers' => 'Chauffeurs',
        'managers' => 'Managers',
        'actif' => 'Membres actifs',
        'sympathisant' => 'Sympathisants',
    ];
}

function communication_storage_dir(): string
{
    $path = dirname(__DIR__) . '/db/email_attachments';
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }

    return $path;
}

function communication_normalize_email_list(?string $value): array
{
    $value = str_replace(["\r", "\n", ';'], [',', ',', ','], (string) $value);
    $chunks = array_filter(array_map('trim', explode(',', $value)));
    $emails = [];

    foreach ($chunks as $email) {
        $normalized = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($normalized) {
            $emails[] = strtolower($normalized);
        }
    }

    return array_values(array_unique($emails));
}

function communication_resolve_recipients(string $audience, ?string $extraRecipients = null): array
{
    $sql = 'SELECT Person.id, Person.email FROM Person INNER JOIN Member ON Member.person = Person.id';
    $params = [];

    if ($audience !== 'all') {
        if ($audience === 'drivers') {
            $sql = 'SELECT Person.id, Person.email FROM Person INNER JOIN Driver ON Driver.person = Person.id';
        } elseif ($audience === 'managers') {
            $sql = 'SELECT Person.id, Person.email FROM Person INNER JOIN Manager ON Manager.person = Person.id';
        } else {
            $sql .= ' WHERE Member.type = ?';
            $params[] = $audience;
        }
    }

    $rows = fetch_all($sql, $params);
    $personIds = [];
    $emails = [];

    foreach ($rows as $row) {
        $personIds[] = (int) $row['id'];
        if (!empty($row['email'])) {
            $emails[] = strtolower((string) $row['email']);
        }
    }

    foreach (communication_normalize_email_list($extraRecipients) as $email) {
        $emails[] = $email;
    }

    $emails = array_values(array_unique($emails));

    return [
        'person_ids' => array_values(array_unique($personIds)),
        'emails' => $emails,
    ];
}

function communication_fetch_attachments(int $messageId): array
{
    return fetch_all(
        'SELECT id, message, originalName, storedName, mimeType, size, createdAt
         FROM MessageAttachment
         WHERE message = ?
         ORDER BY id ASC',
        [$messageId]
    );
}

function communication_fetch_message(int $messageId): ?array
{
    $message = fetch_one(
        'SELECT
            Message.*,
            Content.label,
            Content.body
         FROM Message
         INNER JOIN Content ON Content.id = Message.content
         WHERE Message.id = ?',
        [$messageId]
    );

    if (!$message) {
        return null;
    }

    $message['attachments'] = communication_fetch_attachments((int) $message['id']);

    return $message;
}

function communication_upsert_message(?int $messageId, int $userId, string $audience, string $subject, string $body, string $extraRecipients = ''): int
{
    $now = now_iso();

    if ($messageId) {
        $existing = communication_fetch_message($messageId);
        if (!$existing) {
            throw new RuntimeException('Brouillon introuvable.');
        }

        db()->prepare('UPDATE Content SET label = ?, body = ? WHERE id = ?')
            ->execute([$subject, $body, (int) $existing['content']]);
        db()->prepare(
            'UPDATE Message
             SET msgFrom = ?, audience = ?, extraRecipients = ?, updatedAt = ?, smtpError = NULL
             WHERE id = ?'
        )->execute([$userId, $audience, $extraRecipients, $now, $messageId]);

        return $messageId;
    }

    db()->prepare('INSERT INTO Content(msgFrom, label, body) VALUES (?, ?, ?)')
        ->execute([$userId, $subject, $body]);
    $contentId = (int) db()->lastInsertId();

    db()->prepare(
        'INSERT INTO Message(content, msgFrom, status, audience, extraRecipients, updatedAt)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$contentId, $userId, 'draft', $audience, $extraRecipients, $now]);

    return (int) db()->lastInsertId();
}

function communication_store_uploaded_attachments(int $messageId, array $files): void
{
    if (empty($files['name'])) {
        return;
    }

    $names = is_array($files['name']) ? $files['name'] : [$files['name']];
    $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
    $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
    $types = is_array($files['type']) ? $files['type'] : [$files['type']];
    $storageDir = communication_storage_dir();
    $finfo = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;

    foreach ($names as $index => $originalName) {
        $error = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Echec du televersement d une piece jointe.');
        }

        $tmpName = (string) ($tmpNames[$index] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Fichier de piece jointe invalide.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string) $originalName));
        $storedName = sprintf('%s_%s', bin2hex(random_bytes(8)), $safeName ?: 'attachment.bin');
        $targetPath = $storageDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('Impossible d enregistrer la piece jointe.');
        }

        $mimeType = $finfo ? (string) $finfo->file($targetPath) : (string) ($types[$index] ?? 'application/octet-stream');
        db()->prepare(
            'INSERT INTO MessageAttachment(message, originalName, storedName, mimeType, size)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $messageId,
            (string) $originalName,
            $storedName,
            $mimeType ?: 'application/octet-stream',
            (int) ($sizes[$index] ?? filesize($targetPath)),
        ]);
    }
}

function communication_delete_attachment(int $attachmentId): void
{
    $attachment = fetch_one('SELECT * FROM MessageAttachment WHERE id = ?', [$attachmentId]);
    if (!$attachment) {
        return;
    }

    $path = communication_storage_dir() . DIRECTORY_SEPARATOR . $attachment['storedName'];
    if (is_file($path)) {
        unlink($path);
    }

    db()->prepare('DELETE FROM MessageAttachment WHERE id = ?')->execute([$attachmentId]);
}

function communication_delete_message(int $messageId): void
{
    $message = communication_fetch_message($messageId);
    if (!$message) {
        return;
    }

    foreach ($message['attachments'] as $attachment) {
        communication_delete_attachment((int) $attachment['id']);
    }

    db()->prepare('DELETE FROM Message WHERE id = ?')->execute([$messageId]);
    db()->prepare('DELETE FROM Content WHERE id = ?')->execute([(int) $message['content']]);
}

function communication_smtp_settings(): array
{
    return [
        'host' => trim((string) setting('smtp_host', '')),
        'port' => (int) setting('smtp_port', '587'),
        'username' => trim((string) setting('smtp_username', '')),
        'password' => (string) setting('smtp_password', ''),
        'from_email' => trim((string) setting('smtp_from_email', '')),
        'from_name' => trim((string) setting('smtp_from_name', setting('club_name', 'CVLG'))),
        'reply_to' => trim((string) setting('smtp_reply_to', '')),
    ];
}

function communication_smtp_ready(): bool
{
    $settings = communication_smtp_settings();

    return $settings['host'] !== ''
        && $settings['port'] > 0
        && $settings['username'] !== ''
        && $settings['password'] !== ''
        && filter_var($settings['from_email'], FILTER_VALIDATE_EMAIL);
}

function communication_send_message(int $messageId): array
{
    $message = communication_fetch_message($messageId);
    if (!$message) {
        throw new RuntimeException('Brouillon introuvable.');
    }

    $subject = trim((string) $message['label']);
    $body = trim((string) $message['body']);
    if ($subject === '' || $body === '') {
        throw new RuntimeException('Le sujet et le message sont obligatoires.');
    }

    $recipients = communication_resolve_recipients(
        (string) ($message['audience'] ?: 'all'),
        (string) ($message['extraRecipients'] ?? '')
    );
    if (count($recipients['emails']) === 0) {
        throw new RuntimeException('Aucun destinataire email valide.');
    }

    $smtp = communication_smtp_settings();
    if (!communication_smtp_ready()) {
        throw new RuntimeException('La configuration SMTP est incomplete.');
    }

    smtp_send_mail([
        'host' => $smtp['host'],
        'port' => $smtp['port'],
        'username' => $smtp['username'],
        'password' => $smtp['password'],
        'from_email' => $smtp['from_email'],
        'from_name' => $smtp['from_name'],
        'reply_to' => $smtp['reply_to'],
        'to' => $recipients['emails'],
        'subject' => $subject,
        'text_body' => $body,
        'html_body' => nl2br(e($body)),
        'attachments' => array_map(
            static function (array $attachment): array {
                return [
                    'name' => (string) $attachment['originalName'],
                    'type' => (string) $attachment['mimeType'],
                    'path' => communication_storage_dir() . DIRECTORY_SEPARATOR . $attachment['storedName'],
                ];
            },
            $message['attachments']
        ),
    ]);

    db()->prepare(
        'UPDATE Message
         SET status = ?, sent = ?, recipients = ?, recipientEmails = ?, smtpError = NULL, updatedAt = ?
         WHERE id = ?'
    )->execute([
        'sent',
        now_iso(),
        json_encode($recipients['person_ids'], JSON_UNESCAPED_UNICODE),
        json_encode($recipients['emails'], JSON_UNESCAPED_UNICODE),
        now_iso(),
        $messageId,
    ]);

    return [
        'recipient_count' => count($recipients['emails']),
    ];
}

function smtp_send_mail(array $message): void
{
    $socket = @stream_socket_client(
        sprintf('tcp://%s:%d', $message['host'], $message['port']),
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        throw new RuntimeException(sprintf('Connexion SMTP impossible: %s', $errstr ?: $errno));
    }

    stream_set_timeout($socket, 20);

    try {
        smtp_expect($socket, [220]);
        $hostname = gethostname() ?: 'localhost';
        smtp_command($socket, 'EHLO ' . $hostname, [250]);
        smtp_command($socket, 'STARTTLS', [220]);

        $cryptoEnabled = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($cryptoEnabled !== true) {
            throw new RuntimeException('Activation TLS impossible.');
        }

        smtp_command($socket, 'EHLO ' . $hostname, [250]);
        smtp_command($socket, 'AUTH LOGIN', [334]);
        smtp_command($socket, base64_encode((string) $message['username']), [334]);
        smtp_command($socket, base64_encode((string) $message['password']), [235]);

        smtp_command($socket, 'MAIL FROM:<' . $message['from_email'] . '>', [250]);
        foreach ($message['to'] as $recipient) {
            smtp_command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
        }

        smtp_command($socket, 'DATA', [354]);
        smtp_write($socket, smtp_build_message($message) . "\r\n.\r\n");
        smtp_expect($socket, [250]);
        smtp_command($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }
}

function smtp_build_message(array $message): string
{
    $mixedBoundary = 'mixed_' . bin2hex(random_bytes(8));
    $altBoundary = 'alt_' . bin2hex(random_bytes(8));
    $fromName = trim((string) ($message['from_name'] ?? ''));
    $fromEmail = (string) $message['from_email'];
    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . smtp_format_address($fromEmail, $fromName),
        'To: ' . implode(', ', array_map(static fn(string $email): string => smtp_format_address($email), $message['to'])),
        'Subject: ' . smtp_encode_header((string) $message['subject']),
        'MIME-Version: 1.0',
        'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"',
    ];

    if (!empty($message['reply_to']) && filter_var($message['reply_to'], FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . smtp_format_address((string) $message['reply_to']);
    }

    $parts = [];
    $parts[] = '--' . $mixedBoundary;
    $parts[] = 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"';
    $parts[] = '';
    $parts[] = '--' . $altBoundary;
    $parts[] = 'Content-Type: text/plain; charset=UTF-8';
    $parts[] = 'Content-Transfer-Encoding: base64';
    $parts[] = '';
    $parts[] = chunk_split(base64_encode((string) $message['text_body']));
    $parts[] = '--' . $altBoundary;
    $parts[] = 'Content-Type: text/html; charset=UTF-8';
    $parts[] = 'Content-Transfer-Encoding: base64';
    $parts[] = '';
    $parts[] = chunk_split(base64_encode((string) $message['html_body']));
    $parts[] = '--' . $altBoundary . '--';

    foreach ($message['attachments'] as $attachment) {
        $path = (string) $attachment['path'];
        if (!is_file($path)) {
            continue;
        }
        $parts[] = '--' . $mixedBoundary;
        $parts[] = 'Content-Type: ' . ((string) $attachment['type'] ?: 'application/octet-stream') . '; name="' . addslashes((string) $attachment['name']) . '"';
        $parts[] = 'Content-Transfer-Encoding: base64';
        $parts[] = 'Content-Disposition: attachment; filename="' . addslashes((string) $attachment['name']) . '"';
        $parts[] = '';
        $parts[] = chunk_split(base64_encode((string) file_get_contents($path)));
    }

    $parts[] = '--' . $mixedBoundary . '--';
    $payload = implode("\r\n", array_merge($headers, [''], $parts));

    return preg_replace("/(?m)^\./", '..', $payload) ?? $payload;
}

function smtp_format_address(string $email, string $name = ''): string
{
    if ($name === '') {
        return $email;
    }

    return smtp_encode_header($name) . ' <' . $email . '>';
}

function smtp_encode_header(string $value): string
{
    if ($value === '' || !preg_match('/[^\x20-\x7E]/', $value)) {
        return $value;
    }

    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function smtp_command($socket, string $command, array $expectedCodes): string
{
    smtp_write($socket, $command . "\r\n");
    return smtp_expect($socket, $expectedCodes);
}

function smtp_write($socket, string $data): void
{
    $length = strlen($data);
    $offset = 0;

    while ($offset < $length) {
        $written = fwrite($socket, substr($data, $offset));
        if ($written === false || $written === 0) {
            throw new RuntimeException('Ecriture SMTP impossible.');
        }
        $offset += $written;
    }
}

function smtp_expect($socket, array $expectedCodes): string
{
    $response = '';

    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $response .= $line;

        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('Erreur SMTP: ' . trim($response));
    }

    return $response;
}
