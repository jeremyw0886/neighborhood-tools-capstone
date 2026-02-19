<?php
/**
 * Dispute Detail — subject, context, and chronological message thread.
 *
 * Variables from DisputeController::show():
 *   $dispute   array  Row from Dispute::findByIdWithContext()
 *   $messages  array  Rows from Dispute::getMessages() (internal notes pre-filtered)
 *   $isAdmin   bool   Whether the current user is an admin
 *
 * Shared data:
 *   $authUser   array{id, name, first_name, role, avatar}
 *   $csrfToken  string
 */

$subject      = htmlspecialchars($dispute['subject_text_dsp']);
$toolName     = htmlspecialchars($dispute['tool_name_tol']);
$borrowerName = htmlspecialchars($dispute['borrower_name']);
$lenderName   = htmlspecialchars($dispute['lender_name']);
$reporterName = htmlspecialchars($dispute['reporter_name']);
$status       = htmlspecialchars($dispute['dispute_status']);
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
    <?php if ($isAdmin): ?>
      <a href="/admin/disputes">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> All Disputes
      </a>
    <?php else: ?>
      <a href="/dashboard">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Dashboard
      </a>
    <?php endif; ?>
  </nav>

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
            $isOwnMsg  = (int) $msg['author_id'] === $authUser['id'];
            $avatar    = $msg['author_avatar']
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
              <?= nl2br(htmlspecialchars($msg['message_text_dsm'])) ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>

</section>
