<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_roles(['R']);
$audienceOptions = communication_audience_options();
$editingMessage = null;

if (isset($_GET['edit'])) {
    $editingMessage = communication_fetch_message((int) $_GET['edit']);
    if (!$editingMessage || $editingMessage['status'] !== 'draft') {
        flash('error', 'Brouillon introuvable.');
        redirect('communications.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['submit_action'] ?? 'save';
    $messageId = !empty($_POST['message_id']) ? (int) $_POST['message_id'] : null;

    try {
        if ($action === 'delete') {
            if (!$messageId) {
                throw new RuntimeException('Brouillon introuvable.');
            }
            communication_delete_message($messageId);
            flash('success', 'Brouillon supprime.');
            redirect('communications.php');
        }

        $existingMessage = $messageId ? communication_fetch_message($messageId) : null;
        $audience = (string) ($_POST['audience'] ?? ($existingMessage['audience'] ?? 'all'));
        $subject = trim((string) ($_POST['subject'] ?? ($existingMessage['label'] ?? '')));
        $body = trim((string) ($_POST['body'] ?? ($existingMessage['body'] ?? '')));
        $extraRecipients = trim((string) ($_POST['extra_recipients'] ?? ($existingMessage['extraRecipients'] ?? '')));

        if (!array_key_exists($audience, $audienceOptions)) {
            $audience = 'all';
        }
        if ($subject === '' || $body === '') {
            throw new RuntimeException('Le sujet et le message sont obligatoires.');
        }

        $messageId = communication_upsert_message($messageId, (int) $user['id'], $audience, $subject, $body, $extraRecipients);

        foreach ((array) ($_POST['remove_attachment_ids'] ?? []) as $attachmentId) {
            communication_delete_attachment((int) $attachmentId);
        }

        if (isset($_FILES['attachments'])) {
            communication_store_uploaded_attachments($messageId, $_FILES['attachments']);
        }

        if ($action === 'send') {
            $result = communication_send_message($messageId);
            flash('success', sprintf('Email envoye a %d destinataire(s).', $result['recipient_count']));
        } else {
            flash('success', 'Brouillon enregistre.');
        }

        redirect('communications.php');
    } catch (Throwable $e) {
        if ($messageId) {
            db()->prepare('UPDATE Message SET smtpError = ?, updatedAt = ? WHERE id = ?')
                ->execute([$e->getMessage(), now_iso(), $messageId]);
        }
        flash('error', $e->getMessage());
        $editingMessage = $messageId ? communication_fetch_message($messageId) : [
            'id' => null,
            'audience' => $audience ?? 'all',
            'extraRecipients' => $extraRecipients ?? '',
            'label' => $subject ?? '',
            'body' => $body ?? '',
            'attachments' => [],
        ];
    }
}

$messages = fetch_all(
    'SELECT
        Message.id,
        Message.status,
        Message.sent,
        Message.audience,
        Message.extraRecipients,
        Message.recipients,
        Message.recipientEmails,
        Message.smtpError,
        Message.updatedAt,
        Content.label,
        Content.body,
        (
            SELECT COUNT(*)
            FROM MessageAttachment
            WHERE MessageAttachment.message = Message.id
        ) AS attachmentCount
     FROM Message
     INNER JOIN Content ON Content.id = Message.content
     ORDER BY Message.id DESC'
);

$formMessage = $editingMessage ?? [
    'id' => null,
    'audience' => 'all',
    'extraRecipients' => '',
    'label' => '',
    'body' => '',
    'attachments' => [],
];

$smtpReady = communication_smtp_ready();

render_header('Communication', $user, [
    'actions' => [
        ['href' => 'configuration.php', 'label' => 'Configurer SMTP', 'class' => 'blue-grey'],
    ],
]);
?>
<div class="row">
    <div class="col s12">
        <div class="soft-box" style="padding-bottom: 8px;">
            <h5>Envoi email SMTP</h5>
            <p>
                Statut SMTP :
                <strong><?= $smtpReady ? 'configure' : 'incomplet' ?></strong><br>
                Compte expediteur : <?= e(setting('smtp_from_email', '-')) ?><br>
                Serveur : <?= e(setting('smtp_host', '-')) ?>:<?= e(setting('smtp_port', '587')) ?>
            </p>
            <?php if (!$smtpReady): ?>
                <p class="red-text text-darken-2">Completez les parametres SMTP dans Configuration avant l envoi effectif des emails.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col s12 l5">
        <div class="soft-box">
            <h5><?= $formMessage['id'] ? 'Modifier le brouillon' : 'Nouveau brouillon email' ?></h5>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="message_id" value="<?= e($formMessage['id'] ? (string) $formMessage['id'] : '') ?>">
                <div class="input-field">
                    <select name="audience">
                        <?php foreach ($audienceOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $formMessage['audience'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Audience interne</label>
                </div>
                <div class="input-field">
                    <input type="text" id="extra_recipients" name="extra_recipients" value="<?= e((string) $formMessage['extraRecipients']) ?>">
                    <label for="extra_recipients" class="active">Emails supplementaires</label>
                    <span class="helper-text">Adresses separees par une virgule, en plus de l audience choisie.</span>
                </div>
                <div class="input-field">
                    <input type="text" id="subject" name="subject" required value="<?= e((string) $formMessage['label']) ?>">
                    <label for="subject" class="active">Sujet</label>
                </div>
                <div class="input-field">
                    <textarea id="body" name="body" class="materialize-textarea" required><?= e((string) $formMessage['body']) ?></textarea>
                    <label for="body" class="active">Message</label>
                </div>
                <div class="file-field input-field">
                    <div class="btn blue-grey">
                        <span>Fichiers</span>
                        <input type="file" name="attachments[]" multiple>
                    </div>
                    <div class="file-path-wrapper">
                        <input class="file-path validate" type="text" placeholder="Ajouter des pieces jointes">
                    </div>
                </div>
                <?php if (!empty($formMessage['attachments'])): ?>
                    <div style="margin-bottom: 20px;">
                        <strong>Pieces jointes existantes</strong>
                        <?php foreach ($formMessage['attachments'] as $attachment): ?>
                            <p style="margin: 8px 0;">
                                <label>
                                    <input type="checkbox" name="remove_attachment_ids[]" value="<?= e((string) $attachment['id']) ?>">
                                    <span>Supprimer <?= e($attachment['originalName']) ?> (<?= e((string) $attachment['size']) ?> octets)</span>
                                </label>
                            </p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <button class="btn blue-grey" type="submit" name="submit_action" value="save">Enregistrer le brouillon</button>
                <button class="btn" type="submit" name="submit_action" value="send">Envoyer l email</button>
                <?php if ($formMessage['id']): ?>
                    <a class="btn-flat" href="communications.php">Annuler</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col s12 l7">
        <div class="soft-box">
            <h5>Emails et brouillons</h5>
            <table class="striped responsive-table">
                <thead>
                <tr>
                    <th>Sujet</th>
                    <th>Audience</th>
                    <th>Statut</th>
                    <th>Destinataires</th>
                    <th>Pieces jointes</th>
                    <th>Envoi</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($messages as $message): ?>
                    <?php
                    $recipientEmails = json_decode((string) ($message['recipientEmails'] ?: '[]'), true);
                    if (!is_array($recipientEmails)) {
                        $recipientEmails = [];
                    }
                    $recipientIds = json_decode((string) ($message['recipients'] ?: '[]'), true);
                    if (!is_array($recipientIds)) {
                        $recipientIds = [];
                    }
                    $recipientCount = count($recipientEmails) > 0 ? count($recipientEmails) : count($recipientIds);
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($message['label']) ?></strong><br>
                            <small><?= e(strlen((string) $message['body']) > 90 ? substr((string) $message['body'], 0, 87) . '...' : (string) $message['body']) ?></small>
                            <?php if (!empty($message['smtpError'])): ?>
                                <br><small class="red-text text-darken-2"><?= e((string) $message['smtpError']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($audienceOptions[$message['audience'] ?: 'all'] ?? (string) $message['audience']) ?></td>
                        <td><?= e(translate_status($message['status'])) ?></td>
                        <td><?= e((string) $recipientCount) ?></td>
                        <td><?= e((string) $message['attachmentCount']) ?></td>
                        <td><?= e($message['sent'] ?: '-') ?></td>
                        <td>
                            <?php if ($message['status'] === 'draft'): ?>
                                <a href="communications.php?edit=<?= e((string) $message['id']) ?>">Modifier</a>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="message_id" value="<?= e((string) $message['id']) ?>">
                                    <button class="btn-flat" type="submit" name="submit_action" value="delete" onclick="return confirm('Supprimer ce brouillon ?');">Supprimer</button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="message_id" value="<?= e((string) $message['id']) ?>">
                                    <button class="btn-flat" type="submit" name="submit_action" value="send">Envoyer</button>
                                </form>
                            <?php else: ?>
                                <span class="grey-text">Envoye</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php render_footer(); ?>
