<?php
/**
 * Dispute Detail — subject, context, and chronological message thread.
 *
 * Variables from DisputeController::show():
 *
 * @var array  $dispute    Row from Dispute::findByIdWithContext()
 * @var array  $messages   Rows from Dispute::getMessages() (internal notes pre-filtered)
 * @var bool   $isAdmin    Whether the current user is an admin
 * @var array  $msgErrors  Field-keyed validation errors for the reply form
 * @var array  $msgOld     Previous input values for sticky reply field
 * @var string $msgSuccess Success flash after posting a message
 *
 * Shared data:
 *
 * @var array{id, name, first_name, role, avatar} $authUser
 * @var string                                    $csrfToken
 */

$disputeId    = (int) $dispute['id_dsp'];
$subject      = htmlspecialchars($dispute['subject_text_dsp']);
$toolName     = htmlspecialchars($dispute['tool_name_tol']);
$borrowerName = htmlspecialchars($dispute['borrower_name']);
$lenderName   = htmlspecialchars($dispute['lender_name']);
$reporterName = htmlspecialchars($dispute['reporter_name']);
$status       = htmlspecialchars($dispute['dispute_status']);
$isOpen       = $dispute['dispute_status'] === 'open';
$filedDate    = date('M j, Y \a\t g:i A', strtotime($dispute['created_at_dsp']));
$daysOpen     = (int) $dispute['days_open'];
$resolvedAt   = $dispute['resolved_at_dsp']
    ? date('M j, Y \a\t g:i A', strtotime($dispute['resolved_at_dsp']))
    : null;

$typeLabels = [
    'initial_report' => 'Initial Report',
    'response'       => 'Response',
    'admin_note'     => 'Admin Note',
    'resolution'     => 'Resolution',
];

$typeIcons = [
    'initial_report' => 'fa-flag',
    'response'       => 'fa-reply',
    'admin_note'     => 'fa-shield-halved',
    'resolution'     => 'fa-check-circle',
];
?>

<section id="dispute-show" aria-labelledby="dispute-show-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <?php if ($msgSuccess !== ''): ?>
    <p role="status" data-flash="success">
      <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
      <?= htmlspecialchars($msgSuccess) ?>
    </p>
  <?php endif; ?>

  <header>
    <h1 id="dispute-show-heading">
      <i class="fa-solid fa-gavel" aria-hidden="true"></i>
      <?= $subject ?>
    </h1>
    <p data-status="<?= htmlspecialchars(strtolower($dispute['dispute_status'])) ?>">
      <i class="fa-solid fa-circle" aria-hidden="true"></i>
      <?= $status ?>
    </p>
  </header>

  <dl aria-label="Dispute details">
    <div>
      <dt>Tool</dt>
      <dd><?= $toolName ?></dd>
    </div>
    <div>
      <dt>Filed By</dt>
      <dd><?= $reporterName ?></dd>
    </div>
    <div>
      <dt>Borrower</dt>
      <dd><?= $borrowerName ?></dd>
    </div>
    <div>
      <dt>Lender</dt>
      <dd><?= $lenderName ?></dd>
    </div>
    <div>
      <dt>Filed</dt>
      <dd><time datetime="<?= htmlspecialchars($dispute['created_at_dsp']) ?>"><?= $filedDate ?></time></dd>
    </div>
    <?php if ($resolvedAt !== null): ?>
      <div>
        <dt>Resolved</dt>
        <dd><time datetime="<?= htmlspecialchars($dispute['resolved_at_dsp']) ?>"><?= $resolvedAt ?></time></dd>
      </div>
    <?php else: ?>
      <div>
        <dt>Open</dt>
        <dd><?= $daysOpen ?> day<?= $daysOpen !== 1 ? 's' : '' ?></dd>
      </div>
    <?php endif; ?>
  </dl>

  <section aria-labelledby="thread-heading">
    <h2 id="thread-heading">
      <i class="fa-solid fa-comments" aria-hidden="true"></i>
      Message Thread
      <span>(<?= count($messages) ?>)</span>
    </h2>

    <?php if ($messages === []): ?>
      <p>No messages yet.</p>
    <?php else: ?>
      <ol aria-label="Dispute messages">
        <?php foreach ($messages as $msg):
            $type      = $msg['message_type'];
            $typeLabel = $typeLabels[$type] ?? ucwords(str_replace('_', ' ', $type));
            $typeIcon  = $typeIcons[$type] ?? 'fa-message';
            $avatar    = ($msg['author_avatar']
                && file_exists(BASE_PATH . '/public/uploads/profiles/' . $msg['author_avatar']))
                ? '/uploads/profiles/' . htmlspecialchars($msg['author_avatar'])
                : '/assets/images/avatar-placeholder.svg';
            $timestamp = date('M j, Y \a\t g:i A', strtotime($msg['created_at_dsm']));
        ?>
          <li data-message-type="<?= htmlspecialchars($type) ?>"<?php if ($msg['is_internal_dsm']): ?> data-internal<?php endif; ?>>
            <header>
              <img src="<?= $avatar ?>"
                   alt=""
                   width="36" height="36"
                   loading="lazy"
                   decoding="async">
              <div>
                <strong><?= htmlspecialchars($msg['author_name']) ?></strong>
                <span>
                  <i class="fa-solid <?= $typeIcon ?>" aria-hidden="true"></i>
                  <?= htmlspecialchars($typeLabel) ?>
                </span>
              </div>
              <time datetime="<?= htmlspecialchars($msg['created_at_dsm']) ?>"><?= $timestamp ?></time>
            </header>
            <div>
              <?= nl2br(htmlspecialchars($msg['message_text_dsm']), false) ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>

  <?php if ($isOpen): ?>
    <section aria-labelledby="reply-heading">
      <h2 id="reply-heading">
        <i class="fa-solid fa-reply" aria-hidden="true"></i>
        Post a Reply
      </h2>

      <?php if (!empty($msgErrors)): ?>
        <ul role="alert" aria-label="Reply errors">
          <?php foreach ($msgErrors as $msg): ?>
            <li><?= htmlspecialchars($msg) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form action="/disputes/<?= $disputeId ?>/message" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <fieldset>
          <legend>Your Message</legend>

          <?php if ($isAdmin): ?>
            <div>
              <label for="reply-type">Message Type</label>
              <select id="reply-type" name="message_type">
                <option value="response">Response</option>
                <option value="admin_note"<?= ($msgOld['message_type'] ?? '') === 'admin_note' ? ' selected' : '' ?>>Admin Note</option>
                <option value="resolution"<?= ($msgOld['message_type'] ?? '') === 'resolution' ? ' selected' : '' ?>>Resolution</option>
              </select>
            </div>

            <div>
              <label>
                <input type="checkbox"
                       name="is_internal"
                       value="1"
                       <?php if (!empty($msgOld['is_internal'])): ?>checked<?php endif; ?>>
                Internal only (hidden from borrower and lender)
              </label>
            </div>
          <?php endif; ?>

          <div>
            <label for="reply-message">Message <span aria-hidden="true">*</span></label>
            <textarea id="reply-message"
                      name="message"
                      required
                      rows="4"
                      maxlength="5000"
                      placeholder="Type your response…"
                      <?php if (isset($msgErrors['message'])): ?>aria-invalid="true" aria-describedby="reply-error"<?php endif; ?>><?= htmlspecialchars($msgOld['message'] ?? '') ?></textarea>
            <?php if (isset($msgErrors['message'])): ?>
              <p id="reply-error" role="alert"><?= htmlspecialchars($msgErrors['message']) ?></p>
            <?php endif; ?>
          </div>
        </fieldset>

        <footer>
          <button type="submit" data-intent="primary">
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send Message
          </button>
        </footer>
      </form>
    </section>
  <?php else: ?>
    <p data-closed-notice>
      <i class="fa-solid fa-lock" aria-hidden="true"></i>
      This dispute is <?= $status ?> — no further messages can be posted.
    </p>
  <?php endif; ?>

</section>
