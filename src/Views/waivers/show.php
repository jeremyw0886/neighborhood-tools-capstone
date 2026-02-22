<?php
/**
 * Borrow Waiver — acknowledgment form before tool pickup.
 *
 * Variables from WaiverController::show():
 *   $waiver      array   Row from Waiver::findPendingByBorrowId()
 *   $waiverTypes array   Rows from Waiver::getTypes()
 *
 * Shared data:
 *   $authUser   array{id, name, first_name, role, avatar}
 *   $csrfToken  string
 */

$borrowId      = (int) $waiver['id_bor'];
$toolName      = htmlspecialchars($waiver['tool_name_tol']);
$lenderName    = htmlspecialchars($waiver['lender_name']);
$lenderId      = (int) $waiver['lender_id'];
$approvedAt    = date('M j, Y \a\t g:i A', strtotime($waiver['approved_at_bor']));
$hoursPending  = (int) $waiver['hours_since_approval'];
$conditions    = $waiver['preexisting_conditions_tol'];
$depositReq    = (bool) $waiver['is_deposit_required_tol'];
$depositAmount = $waiver['default_deposit_amount_tol'];
?>

<section id="waiver-show" aria-labelledby="waiver-heading">

  <nav aria-label="Back">
    <a href="/dashboard">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Dashboard
    </a>
  </nav>

  <header>
    <h1 id="waiver-heading">
      <i class="fa-solid fa-file-signature" aria-hidden="true"></i>
      Borrow Waiver
    </h1>
    <p>Review the details below and sign the waiver to proceed with picking up <strong><?= $toolName ?></strong>.</p>
  </header>

  <dl aria-label="Borrow details">
    <div>
      <dt>Tool</dt>
      <dd><?= $toolName ?></dd>
    </div>
    <div>
      <dt>Lender</dt>
      <dd>
        <a href="/profile/<?= $lenderId ?>">
          <?= $lenderName ?>
        </a>
      </dd>
    </div>
    <div>
      <dt>Approved</dt>
      <dd>
        <time datetime="<?= htmlspecialchars($waiver['approved_at_bor']) ?>"><?= $approvedAt ?></time>
        <span>(<?= $hoursPending ?> hour<?= $hoursPending !== 1 ? 's' : '' ?> ago)</span>
      </dd>
    </div>
    <?php if ($depositReq): ?>
      <div>
        <dt>Deposit Required</dt>
        <dd>$<?= htmlspecialchars(number_format((float) $depositAmount, 2)) ?></dd>
      </div>
    <?php endif; ?>
  </dl>

  <?php if ($conditions !== null && $conditions !== ''): ?>
    <section aria-labelledby="conditions-heading">
      <h2 id="conditions-heading">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        Pre-existing Conditions
      </h2>
      <blockquote>
        <?= nl2br(htmlspecialchars($conditions)) ?>
      </blockquote>
    </section>
  <?php endif; ?>

  <aside aria-label="Insurance reminder">
    <p>
      <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
      <strong>Insurance Reminder:</strong> NeighborhoodTools recommends that borrowers verify their personal
      property or renter's insurance covers borrowed items. The platform does not provide coverage for
      loss, damage, or injury.
    </p>
  </aside>

  <form method="post" action="/waiver/<?= $borrowId ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <fieldset>
      <legend>Acknowledgments</legend>

      <label>
        <input type="checkbox" name="tool_condition" value="1" required>
        <strong>Tool Condition</strong> — I acknowledge the current condition of this tool<?= ($conditions !== null && $conditions !== '') ? ', including the pre-existing conditions noted above' : '' ?>.
      </label>

      <label>
        <input type="checkbox" name="responsibility" value="1" required>
        <strong>Responsibility</strong> — I accept full responsibility for this tool during the borrow period and agree to return it in the same condition.
      </label>

      <label>
        <input type="checkbox" name="liability" value="1" required>
        <strong>Liability Release</strong> — I understand that NeighborhoodTools is a community platform and I accept the platform's liability limitations as outlined in the <a href="/tos" target="_blank" rel="noopener">Terms of Service</a>.
      </label>
    </fieldset>

    <button type="submit">
      <i class="fa-solid fa-signature" aria-hidden="true"></i>
      Sign Waiver
    </button>
  </form>

</section>
