<?php
/**
 * Handover verification — code entry form for pickup/return confirmation.
 *
 * Variables from HandoverController::verify():
 *   $handover    array  Row from pending_handover_v (code, status, parties, tool)
 *   $isVerifier  bool   Whether the current user is the one who verifies (not the generator)
 *
 * Shared data:
 *   $authUser   array{id, name, first_name, role, avatar}
 *   $csrfToken  string
 */

$type       = $handover['handover_type'];
$codeStatus = $handover['code_status'];
$toolName   = htmlspecialchars($handover['tool_name_tol']);
$borrowId   = (int) $handover['id_bor_hov'];

$statusAttr = match ($codeStatus) {
    'EXPIRED'       => ' data-urgent',
    'EXPIRING SOON' => ' data-warning',
    default         => '',
};

$isPickup = $type === 'pickup';
$typeLabel = $isPickup ? 'Pickup' : 'Return';
$typeIcon  = $isPickup ? 'fa-hand-holding' : 'fa-rotate-left';
?>

<section id="handover-verify" aria-labelledby="handover-heading">

  <header>
    <h1 id="handover-heading">
      <i class="fa-solid <?= $typeIcon ?>" aria-hidden="true"></i>
      <?= $typeLabel ?> Verification
    </h1>
    <p>Confirm the <?= strtolower($typeLabel) ?> of <strong><?= $toolName ?></strong> by entering the verification code.</p>
  </header>

  <?php if (!empty($_SESSION['handover_success'])): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($_SESSION['handover_success']) ?></p>
    <?php unset($_SESSION['handover_success']); ?>
  <?php endif; ?>

  <?php
    $flashError = $_SESSION['handover_errors']['general'] ?? $_SESSION['handover_errors']['code'] ?? '';
    if ($flashError !== ''):
  ?>
    <p role="alert" data-flash="error"><?= htmlspecialchars($flashError) ?></p>
    <?php unset($_SESSION['handover_errors']); ?>
  <?php endif; ?>

  <dl aria-label="Handover details">
    <div>
      <dt>Tool</dt>
      <dd><?= $toolName ?></dd>
    </div>
    <div>
      <dt>Type</dt>
      <dd>
        <i class="fa-solid <?= $typeIcon ?>" aria-hidden="true"></i>
        <?= $typeLabel ?>
      </dd>
    </div>
    <div>
      <dt>Lender</dt>
      <dd>
        <a href="/profile/<?= (int) $handover['lender_id'] ?>">
          <?= htmlspecialchars($handover['lender_name']) ?>
        </a>
      </dd>
    </div>
    <div>
      <dt>Borrower</dt>
      <dd>
        <a href="/profile/<?= (int) $handover['borrower_id'] ?>">
          <?= htmlspecialchars($handover['borrower_name']) ?>
        </a>
      </dd>
    </div>
    <div>
      <dt>Code Status</dt>
      <dd><span<?= $statusAttr ?>><?= htmlspecialchars($codeStatus) ?></span></dd>
    </div>
    <div>
      <dt>Expires</dt>
      <dd>
        <time datetime="<?= htmlspecialchars($handover['expires_at_hov']) ?>">
          <?= htmlspecialchars(date('M j, g:ia', strtotime($handover['expires_at_hov']))) ?>
        </time>
      </dd>
    </div>
    <?php if (!empty($handover['condition_notes_hov'])): ?>
      <div>
        <dt>Condition Notes</dt>
        <dd><?= htmlspecialchars($handover['condition_notes_hov']) ?></dd>
      </div>
    <?php endif; ?>
  </dl>

  <?php if ($isVerifier): ?>

    <?php if ($codeStatus === 'EXPIRED'): ?>
      <p role="alert" data-expired>
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        This verification code has expired. Please ask
        <?= htmlspecialchars($handover['generator_name']) ?>
        to generate a new one.
      </p>
    <?php else: ?>
      <form method="post" action="/handover/<?= $borrowId ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <fieldset>
          <legend>Enter Verification Code</legend>
          <label for="verification-code">
            6-character code from <?= htmlspecialchars($handover['generator_name']) ?>
          </label>
          <input
            type="text"
            id="verification-code"
            name="code"
            required
            minlength="6"
            maxlength="8"
            pattern="[A-Za-z0-9]{6,8}"
            autocomplete="off"
            spellcheck="false"
            placeholder="e.g. A1B2C3"
            value="<?= htmlspecialchars($_SESSION['handover_old']['code'] ?? '') ?>"
          >
          <label for="condition-notes">Condition Notes</label>
          <textarea
            id="condition-notes"
            name="condition_notes"
            rows="3"
            maxlength="2000"
            placeholder="Describe the tool's current condition (optional)"
          ><?= htmlspecialchars($_SESSION['handover_old']['condition_notes'] ?? '') ?></textarea>
          <?php unset($_SESSION['handover_old']); ?>
          <button type="submit">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            Confirm <?= $typeLabel ?>
          </button>
        </fieldset>
      </form>
    <?php endif; ?>

  <?php else: ?>
    <section aria-labelledby="your-code-heading">
      <h2 id="your-code-heading">
        <i class="fa-solid fa-key" aria-hidden="true"></i>
        Your Verification Code
      </h2>
      <p>Share this code with the other party to confirm the <?= strtolower($typeLabel) ?>.</p>
      <p data-code aria-label="Verification code">
        <?= htmlspecialchars($handover['verification_code_hov']) ?>
      </p>
    </section>
  <?php endif; ?>

  <nav aria-label="Navigation">
    <a href="/dashboard">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Back to Dashboard
    </a>
  </nav>

</section>
