<?php
/**
 * Incident Detail — incident report with context, photos, and resolution info.
 *
 * Variables from IncidentController::show():
 *
 * @var array $incident   Row from Incident::findByIdWithContext()
 * @var array $photos     Rows from Incident::getPhotos()
 * @var bool  $isAdmin    Whether the current user is an admin
 * @var bool  $isReporter Whether the current user filed this report
 *
 * Shared data:
 *
 * @var array{id, name, first_name, role, avatar} $authUser
 * @var string                                    $csrfToken
 */

$incidentId    = (int) $incident['id_irt'];
$subject       = htmlspecialchars($incident['subject_irt']);
$description   = $incident['description_irt'];
$incidentType  = htmlspecialchars($incident['incident_type']);
$toolName      = htmlspecialchars($incident['tool_name_tol']);
$reporterName  = htmlspecialchars($incident['reporter_name']);
$borrowerName  = htmlspecialchars($incident['borrower_name']);
$lenderName    = htmlspecialchars($incident['lender_name']);
$daysOpen      = (int) $incident['days_open'];
$withinDeadline = (bool) $incident['is_reported_within_deadline_irt'];
$isResolved    = $incident['resolved_at_irt'] !== null;
$status        = $isResolved ? 'resolved' : 'open';

$occurredAt = date('M j, Y \a\t g:i A', strtotime($incident['incident_occurred_at_irt']));
$reportedAt = date('M j, Y \a\t g:i A', strtotime($incident['created_at_irt']));
$resolvedAt = $isResolved
    ? date('M j, Y \a\t g:i A', strtotime($incident['resolved_at_irt']))
    : null;

$damageAmount    = $incident['estimated_damage_amount_irt'];
$depositAmount   = $incident['deposit_amount'];
$depositStatus   = $incident['deposit_status'];
$relatedDisputes = (int) $incident['related_disputes'];

$typeLabels = [
    'damage'             => 'Damage',
    'theft'              => 'Theft',
    'loss'               => 'Loss',
    'injury'             => 'Injury',
    'late_return'        => 'Late Return',
    'condition_dispute'  => 'Condition Dispute',
    'other'              => 'Other',
];

$typeIcons = [
    'damage'             => 'fa-hammer',
    'theft'              => 'fa-mask',
    'loss'               => 'fa-box-open',
    'injury'             => 'fa-kit-medical',
    'late_return'        => 'fa-clock',
    'condition_dispute'  => 'fa-scale-unbalanced',
    'other'              => 'fa-circle-question',
];

$typeLabel = $typeLabels[$incident['incident_type']] ?? $incidentType;
$typeIcon  = $typeIcons[$incident['incident_type']] ?? 'fa-circle-question';
?>

<section id="incident-show" aria-labelledby="incident-show-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <header>
    <h1 id="incident-show-heading">
      <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
      <?= $subject ?>
    </h1>
    <p data-status="<?= $status ?>">
      <i class="fa-solid fa-circle" aria-hidden="true"></i>
      <?= ucfirst($status) ?>
    </p>
  </header>

  <dl aria-label="Incident details">
    <div>
      <dt>Type</dt>
      <dd>
        <i class="fa-solid <?= $typeIcon ?>" aria-hidden="true"></i>
        <?= htmlspecialchars($typeLabel) ?>
      </dd>
    </div>
    <div>
      <dt>Tool</dt>
      <dd><?= $toolName ?></dd>
    </div>
    <div>
      <dt>Reported By</dt>
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
      <dt>Occurred</dt>
      <dd><time datetime="<?= htmlspecialchars($incident['incident_occurred_at_irt']) ?>"><?= $occurredAt ?></time></dd>
    </div>
    <div>
      <dt>Reported</dt>
      <dd><time datetime="<?= htmlspecialchars($incident['created_at_irt']) ?>"><?= $reportedAt ?></time></dd>
    </div>
    <div>
      <dt>Deadline</dt>
      <dd data-deadline="<?= $withinDeadline ? 'met' : 'missed' ?>">
        <?php if ($withinDeadline): ?>
          <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Within 48 hours
        <?php else: ?>
          <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> After 48-hour window
        <?php endif; ?>
      </dd>
    </div>
    <?php if ($damageAmount !== null): ?>
      <div>
        <dt>Est. Damage</dt>
        <dd>$<?= htmlspecialchars(number_format((float) $damageAmount, 2)) ?></dd>
      </div>
    <?php endif; ?>
    <?php if ($depositAmount !== null): ?>
      <div>
        <dt>Deposit</dt>
        <dd>$<?= htmlspecialchars(number_format((float) $depositAmount, 2)) ?> (<?= htmlspecialchars($depositStatus) ?>)</dd>
      </div>
    <?php endif; ?>
    <?php if ($relatedDisputes > 0): ?>
      <div>
        <dt>Disputes</dt>
        <dd><?= $relatedDisputes ?> related dispute<?= $relatedDisputes !== 1 ? 's' : '' ?></dd>
      </div>
    <?php endif; ?>
    <?php if ($resolvedAt !== null): ?>
      <div>
        <dt>Resolved</dt>
        <dd><time datetime="<?= htmlspecialchars($incident['resolved_at_irt']) ?>"><?= $resolvedAt ?></time></dd>
      </div>
      <?php if ($incident['resolver_name']): ?>
        <div>
          <dt>Resolved By</dt>
          <dd><?= htmlspecialchars($incident['resolver_name']) ?></dd>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div>
        <dt>Open</dt>
        <dd><?= $daysOpen ?> day<?= $daysOpen !== 1 ? 's' : '' ?></dd>
      </div>
    <?php endif; ?>
  </dl>

  <section aria-labelledby="description-heading">
    <h2 id="description-heading">
      <i class="fa-solid fa-align-left" aria-hidden="true"></i>
      Description
    </h2>
    <div>
      <?= nl2br(htmlspecialchars($description), false) ?>
    </div>
  </section>

  <?php if ($incident['resolution_notes_irt'] && $isResolved): ?>
    <section aria-labelledby="resolution-heading">
      <h2 id="resolution-heading">
        <i class="fa-solid fa-check-circle" aria-hidden="true"></i>
        Resolution
      </h2>
      <div>
        <?= nl2br(htmlspecialchars($incident['resolution_notes_irt']), false) ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($resolveSuccess)): ?>
    <p role="status" data-flash="success">
      <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
      <?= htmlspecialchars($resolveSuccess) ?>
    </p>
  <?php endif; ?>

  <?php
    $resolveError = $resolveErrors['general'] ?? $resolveErrors['resolution_notes'] ?? '';
    if ($resolveError !== ''):
  ?>
    <p role="alert" data-flash="error"><?= htmlspecialchars($resolveError) ?></p>
  <?php endif; ?>

  <?php if ($photos !== []): ?>
    <section aria-labelledby="photos-heading">
      <h2 id="photos-heading">
        <i class="fa-solid fa-camera" aria-hidden="true"></i>
        Evidence Photos
        <span>(<?= count($photos) ?>)</span>
      </h2>
      <ul aria-label="Incident photos">
        <?php foreach ($photos as $photo): ?>
          <li>
            <a href="/uploads/incidents/<?= htmlspecialchars($photo['file_name_iph']) ?>"
               target="_blank"
               rel="noopener">
              <img src="/uploads/incidents/<?= htmlspecialchars($photo['file_name_iph']) ?>"
                   alt="<?= htmlspecialchars($photo['caption_iph'] ?? 'Incident evidence photo') ?>"
                   loading="lazy"
                   decoding="async"
                   width="300"
                   height="200">
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <?php if ($isAdmin && !$isResolved): ?>
    <section aria-labelledby="resolve-heading">
      <h2 id="resolve-heading">
        <i class="fa-solid fa-check-circle" aria-hidden="true"></i>
        Resolve Incident
      </h2>
      <form action="/incidents/<?= $incidentId ?>/resolve" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <fieldset>
          <legend class="visually-hidden">Resolution</legend>
          <div>
            <label for="resolution-notes">Resolution Notes <span aria-hidden="true">*</span></label>
            <textarea id="resolution-notes"
                      name="resolution_notes"
                      required
                      rows="4"
                      maxlength="5000"
                      placeholder="Describe how this incident was resolved..."
                      <?php if (isset($resolveErrors['resolution_notes'])): ?>aria-invalid="true"<?php endif; ?>></textarea>
          </div>
        </fieldset>
        <footer>
          <button type="submit" data-intent="success">
            <i class="fa-solid fa-check-circle" aria-hidden="true"></i> Resolve Incident
          </button>
        </footer>
      </form>
    </section>
  <?php endif; ?>

</section>
