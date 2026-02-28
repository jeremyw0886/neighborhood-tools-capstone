<?php
/**
 * Handover verification — code entry form for pickup/return confirmation.
 *
 * Variables from HandoverController::verify():
 *   $handover          array  Row from pending_handover_v (code, status, parties, tool)
 *   $isVerifier        bool   Whether the current user is the one who verifies (not the generator)
 *   $awaitingLender    bool   (optional) True when borrower visits before lender generates pickup code
 *   $awaitingBorrower  bool   (optional) True when lender visits before borrower generates return code
 *   $depositPending    bool   (optional) True when lender visits but borrower hasn't paid deposit
 *   $borrow            array  (optional) Row from Borrow::findById() — present with awaiting/deposit flags
 *
 * Shared data:
 *   $authUser   array{id, name, first_name, role, avatar}
 *   $csrfToken  string
 */

if (!empty($depositPending)):
  $toolName = htmlspecialchars($borrow['tool_name_tol']);
?>

<section id="handover-verify" aria-labelledby="handover-heading">

  <header>
    <h1 id="handover-heading">
      <i class="fa-solid fa-hand-holding" aria-hidden="true"></i>
      Pickup Verification
    </h1>
    <p>Awaiting deposit for <strong><?= $toolName ?></strong>.</p>
  </header>

  <dl aria-label="Borrow details">
    <div>
      <dt>Tool</dt>
      <dd><?= $toolName ?></dd>
    </div>
    <div>
      <dt>Borrower</dt>
      <dd>
        <a href="/profile/<?= (int) $borrow['borrower_id'] ?>">
          <?= htmlspecialchars($borrow['borrower_name']) ?>
        </a>
      </dd>
    </div>
    <div>
      <dt>Status</dt>
      <dd><span data-status="approved">Approved</span></dd>
    </div>
  </dl>

  <p data-flash="info">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    The borrower hasn't paid the security deposit yet. The pickup code can be generated once the deposit is confirmed.
  </p>

  <nav aria-label="Back">
    <a href="/dashboard/lender">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Back to Dashboard
    </a>
  </nav>

</section>

<?php return; endif; ?>

<?php
if (!empty($awaitingLender)):
  $toolName   = htmlspecialchars($borrow['tool_name_tol']);
  $lenderName = htmlspecialchars($borrow['lender_name']);
?>

<section id="handover-verify" aria-labelledby="handover-heading">

  <header>
    <h1 id="handover-heading">
      <i class="fa-solid fa-hand-holding" aria-hidden="true"></i>
      Pickup Verification
    </h1>
    <p>Awaiting pickup code for <strong><?= $toolName ?></strong>.</p>
  </header>

  <dl aria-label="Borrow details">
    <div>
      <dt>Tool</dt>
      <dd><?= $toolName ?></dd>
    </div>
    <div>
      <dt>Lender</dt>
      <dd>
        <a href="/profile/<?= (int) $borrow['lender_id'] ?>">
          <?= $lenderName ?>
        </a>
      </dd>
    </div>
    <div>
      <dt>Status</dt>
      <dd><span data-status="approved">Approved</span></dd>
    </div>
  </dl>

  <p data-flash="info">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    <?= $lenderName ?> hasn't generated the pickup code yet. Once they do, you'll receive a notification.
  </p>

  <nav aria-label="Back">
    <a href="/dashboard/borrower">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Back to Dashboard
    </a>
  </nav>

</section>

<?php return; endif; ?>

<?php
if (!empty($awaitingBorrower)):
  $toolName     = htmlspecialchars($borrow['tool_name_tol']);
  $borrowerName = htmlspecialchars($borrow['borrower_name']);
?>

<section id="handover-verify" aria-labelledby="handover-heading">

  <header>
    <h1 id="handover-heading">
      <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
      Return Verification
    </h1>
    <p>Awaiting return code for <strong><?= $toolName ?></strong>.</p>
  </header>

  <dl aria-label="Borrow details">
    <div>
      <dt>Tool</dt>
      <dd><?= $toolName ?></dd>
    </div>
    <div>
      <dt>Borrower</dt>
      <dd>
        <a href="/profile/<?= (int) $borrow['borrower_id'] ?>">
          <?= $borrowerName ?>
        </a>
      </dd>
    </div>
    <div>
      <dt>Status</dt>
      <dd><span data-status="borrowed">Borrowed</span></dd>
    </div>
  </dl>

  <p data-flash="info">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    <?= $borrowerName ?> hasn't generated the return code yet. Once they do, you'll receive a notification.
  </p>

  <nav aria-label="Back">
    <a href="/dashboard/lender">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Back to Dashboard
    </a>
  </nav>

</section>

<?php return; endif; ?>

<?php
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

  <?php if (!empty($handoverSuccess)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($handoverSuccess) ?></p>
  <?php endif; ?>

  <?php
    $flashError = $handoverErrors['general'] ?? $handoverErrors['code'] ?? '';
    if ($flashError !== ''):
  ?>
    <p role="alert" data-flash="error"><?= htmlspecialchars($flashError) ?></p>
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
            value="<?= htmlspecialchars($handoverOld['code'] ?? '') ?>"
          >
          <label for="condition-notes">Condition Notes</label>
          <textarea
            id="condition-notes"
            name="condition_notes"
            rows="3"
            maxlength="2000"
            placeholder="Describe the tool's current condition (optional)"
          ><?= htmlspecialchars($handoverOld['condition_notes'] ?? '') ?></textarea>
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

  <nav aria-label="Back">
    <a href="/dashboard">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Back to Dashboard
    </a>
  </nav>

</section>
