<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $audience = $_POST['audience'] ?? 'all';
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $status = ($_POST['submit_action'] ?? 'draft') === 'send' ? 'sent' : 'draft';

    $recipientSql = 'SELECT Person.id FROM Person INNER JOIN Member ON Member.person = Person.id';
    $params = [];
    if ($audience !== 'all') {
        if ($audience === 'drivers') {
            $recipientSql = 'SELECT Person.id FROM Person INNER JOIN Driver ON Driver.person = Person.id';
        } elseif ($audience === 'managers') {
            $recipientSql = 'SELECT Person.id FROM Person INNER JOIN Manager ON Manager.person = Person.id';
        } else {
            $recipientSql .= ' WHERE Member.type = ?';
            $params[] = $audience;
        }
    }
    $recipientIds = array_map(static fn($row) => (int) $row['id'], fetch_all($recipientSql, $params));

    db()->prepare('INSERT INTO Content(msgFrom, label, body) VALUES (?, ?, ?)')
        ->execute([(int) $user['id'], $subject, $body]);
    $contentId = (int) db()->lastInsertId();

    db()->prepare('INSERT INTO Message(content, msgFrom, sent, status, recipients) VALUES (?, ?, ?, ?, ?)')
        ->execute([$contentId, (int) $user['id'], $status === 'sent' ? now_iso() : null, $status, json_encode($recipientIds)]);

    flash('success', $status === 'sent' ? 'Message marque comme envoye.' : 'Brouillon enregistre.');
    redirect('communications.php');
}

$messages = fetch_all(
    'SELECT Message.id, Message.status, Message.sent, Message.recipients, Content.label
     FROM Message
     INNER JOIN Content ON Content.id = Message.content
     ORDER BY Message.id DESC'
);

render_header('Communication', $user);
?>
<div class="row">
    <div class="col s12 l5">
        <div class="soft-box">
            <h5>Nouveau message</h5>
            <form method="post">
                <div class="input-field">
                    <select name="audience">
                        <option value="all">Tous les membres</option>
                        <option value="drivers">Chauffeurs</option>
                        <option value="managers">Managers</option>
                        <option value="actif">Membres actifs</option>
                        <option value="sympathisant">Sympathisants</option>
                    </select>
                    <label>Audience</label>
                </div>
                <div class="input-field"><input type="text" id="subject" name="subject" required><label for="subject">Sujet</label></div>
                <div class="input-field"><textarea id="body" name="body" class="materialize-textarea" required></textarea><label for="body">Message</label></div>
                <button class="btn blue-grey" type="submit" name="submit_action" value="draft">Enregistrer le brouillon</button>
                <button class="btn" type="submit" name="submit_action" value="send">Marquer comme envoye</button>
            </form>
        </div>
    </div>
    <div class="col s12 l7">
        <div class="soft-box">
            <h5>Messages</h5>
            <table class="striped">
                <thead><tr><th>Sujet</th><th>Statut</th><th>Destinataires</th><th>Envoi</th></tr></thead>
                <tbody>
                <?php foreach ($messages as $message): ?>
                    <tr>
                        <td><?= e($message['label']) ?></td>
                        <td><?= e($message['status']) ?></td>
                        <td><?= e((string) count(json_decode($message['recipients'] ?: '[]', true))) ?></td>
                        <td><?= e($message['sent'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php render_footer(); ?>
