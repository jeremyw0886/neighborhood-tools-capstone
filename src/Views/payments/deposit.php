<?php if (!empty($paymentMode)): ?>
<?php
$depositId = (int) $deposit['id_sdp'];
$amount    = number_format((float) $deposit['amount_sdp'], 2);
$toolName  = htmlspecialchars($deposit['tool_name_tol']);
$provider  = htmlspecialchars($deposit['payment_provider']);
?>

<section id="deposit-payment" aria-labelledby="payment-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <header>
    <h1 id="payment-heading">
      <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
      Pay Security Deposit
    </h1>
  </header>

  <dl aria-label="Deposit summary">
    <div>
      <dt>Tool</dt>
      <dd><?= $toolName ?></dd>
    </div>
    <div>
      <dt>Amount</dt>
      <dd>$<?= $amount ?></dd>
    </div>
    <div>
      <dt>Provider</dt>
      <dd><?= $provider ?></dd>
    </div>
  </dl>

  <p data-deposit-terms>Your deposit is fully refundable upon safe return of the tool. If damage or loss occurs, part or all of the deposit may be forfeited.</p>

  <?php if (!empty($stripeClientSecret)): ?>
  <form id="payment-form"
        data-publishable-key="<?= htmlspecialchars($stripePublishableKey) ?>"
        data-client-secret="<?= htmlspecialchars($stripeClientSecret) ?>">
    <div id="payment-element"></div>
    <p id="payment-message" role="alert" hidden></p>
    <footer>
      <button type="submit" data-intent="warning">Pay $<?= $amount ?></button>
    </footer>
  </form>
  <?php else: ?>
  <p>Payment processing is temporarily unavailable. Please try again later.</p>
  <?php endif; ?>

</section>

<?php else: ?>
<?php

$old = $depositOld;

$errorMessages = [];
foreach ($depositErrors as $msg) {
    $errorMessages[] = $msg;
}

$depositId     = (int) $deposit['id_sdp'];
$amount        = number_format((float) $deposit['amount_sdp'], 2);
$statusRaw     = strtolower($deposit['deposit_status']);
$status        = htmlspecialchars($deposit['deposit_status']);
$action        = htmlspecialchars($deposit['action_required']);
$actionKey     = strtolower($deposit['action_required']);
$daysHeld      = $deposit['days_held'] !== null ? (int) $deposit['days_held'] : null;
$provider      = htmlspecialchars($deposit['payment_provider']);
$heldDate      = $deposit['held_at_sdp'] !== null
    ? date('M j, Y \a\t g:i A', strtotime($deposit['held_at_sdp']))
    : null;
$toolName      = htmlspecialchars($deposit['tool_name_tol']);
$toolId        = (int) $deposit['id_tol'];
$borrowerName  = htmlspecialchars($deposit['borrower_name']);
$lenderName    = htmlspecialchars($deposit['lender_name']);
$borrowStatus  = htmlspecialchars($deposit['borrow_status']);
$dueDate       = $deposit['due_at_bor']
    ? date('M j, Y \a\t g:i A', strtotime($deposit['due_at_bor']))
    : '—';
$incidentCount   = (int) $deposit['incident_count'];
$estimatedVal    = number_format((float) $deposit['estimated_value_tol'], 2);
$releasedDate    = $deposit['released_at_sdp'] !== null
    ? date('M j, Y \a\t g:i A', strtotime($deposit['released_at_sdp']))
    : null;
$forfeitedDate   = $deposit['forfeited_at_sdp'] !== null
    ? date('M j, Y \a\t g:i A', strtotime($deposit['forfeited_at_sdp']))
    : null;
$forfeitedAmount = $deposit['forfeited_amount_sdp'] !== null
    ? number_format((float) $deposit['forfeited_amount_sdp'], 2)
    : null;
$forfeitReason   = $deposit['forfeiture_reason_sdp'] !== null
    ? htmlspecialchars($deposit['forfeiture_reason_sdp'])
    : null;
$isPartialRelease  = $statusRaw === 'partial_release';
$refundedAmount    = $isPartialRelease && $forfeitedAmount !== null
    ? number_format((float) $deposit['amount_sdp'] - (float) $deposit['forfeited_amount_sdp'], 2)
    : null;
$borrowStatusRaw   = strtolower($deposit['borrow_status']);
$toolReturned      = $borrowStatusRaw === 'returned';
?>

<section id="deposit-detail" aria-labelledby="deposit-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <?php if ($depositSuccess): ?>
    <div role="alert" aria-live="polite" data-flash="success">
      <p><?= htmlspecialchars($depositSuccess) ?></p>
    </div>
  <?php endif; ?>

  <?php if ($errorMessages): ?>
    <div role="alert" aria-live="assertive" data-flash="error">
      <ul>
        <?php foreach ($errorMessages as $msg): ?>
          <li><?= htmlspecialchars($msg) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <header>
    <h1 id="deposit-heading">
      <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
      Security Deposit
    </h1>
    <p data-action="<?= htmlspecialchars($actionKey) ?>">
      <?= $action ?>
    </p>
  </header>

  <dl aria-label="Deposit details">
    <div>
      <dt>Amount</dt>
      <dd>$<?= $amount ?></dd>
    </div>
    <?php if ($isPartialRelease && $forfeitedAmount !== null): ?>
    <div>
      <dt>Refunded</dt>
      <dd>$<?= $refundedAmount ?></dd>
    </div>
    <div>
      <dt>Forfeited</dt>
      <dd>$<?= $forfeitedAmount ?></dd>
    </div>
    <?php elseif ($statusRaw === 'forfeited' && $forfeitedAmount !== null): ?>
    <div>
      <dt>Forfeited</dt>
      <dd>$<?= $forfeitedAmount ?></dd>
    </div>
    <?php endif; ?>
    <?php if ($heldDate): ?>
    <div>
      <dt>Days Held</dt>
      <dd><?= $daysHeld ?> day<?= $daysHeld !== 1 ? 's' : '' ?></dd>
    </div>
    <?php endif; ?>
    <div>
      <dt>Provider</dt>
      <dd><?= $provider ?></dd>
    </div>
    <?php if ($heldDate): ?>
    <div>
      <dt>Held Since</dt>
      <dd><time datetime="<?= htmlspecialchars($deposit['held_at_sdp']) ?>"><?= $heldDate ?></time></dd>
    </div>
    <?php endif; ?>
    <div>
      <dt>Tool Value</dt>
      <dd>$<?= $estimatedVal ?></dd>
    </div>
    <?php if ($releasedDate): ?>
    <div>
      <dt>Refund Approved</dt>
      <dd><time datetime="<?= htmlspecialchars($deposit['released_at_sdp']) ?>"><?= $releasedDate ?></time></dd>
    </div>
    <?php endif; ?>
    <?php if ($forfeitedDate): ?>
    <div>
      <dt>Forfeiture Date</dt>
      <dd><time datetime="<?= htmlspecialchars($deposit['forfeited_at_sdp']) ?>"><?= $forfeitedDate ?></time></dd>
    </div>
    <?php endif; ?>
    <?php if ($forfeitReason): ?>
    <div>
      <dt>Forfeiture Reason</dt>
      <dd><?= $forfeitReason ?></dd>
    </div>
    <?php endif; ?>
  </dl>

  <?php if (in_array($statusRaw, ['pending', 'held'], true)): ?>
  <div data-deposit-outcome="info" role="status">
    <p>
      <i class="fa-solid fa-lock" aria-hidden="true"></i>
      <strong>Your $<?= $amount ?> deposit is being held securely.</strong>
    </p>
    <p>This deposit is fully refundable upon safe return of the tool. If damage or loss occurs, part or all may be forfeited.</p>
  </div>
  <?php elseif ($statusRaw === 'released' && $toolReturned && $isBorrower): ?>
  <div data-deposit-outcome="success" role="status">
    <p>
      <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
      <strong>Your $<?= $amount ?> deposit has been approved for refund.</strong>
    </p>
    <p>The refund will be returned to your original payment method (<?= $provider ?>). Processing typically takes 5–10 business days depending on your bank.</p>
  </div>
  <?php elseif ($statusRaw === 'released' && $toolReturned && $isLender): ?>
  <div data-deposit-outcome="success" role="status">
    <p>
      <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
      <strong>The $<?= $amount ?> deposit has been refunded to the borrower.</strong>
    </p>
    <p>The tool was returned in acceptable condition and the full deposit has been released.</p>
  </div>
  <?php elseif ($statusRaw === 'forfeited'): ?>
  <div data-deposit-outcome="danger" role="status">
    <p>
      <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
      <?php if ($isBorrower): ?>
      <strong>Your deposit of $<?= $forfeitedAmount ?? $amount ?> has been forfeited.</strong>
      <?php else: ?>
      <strong>The deposit of $<?= $forfeitedAmount ?? $amount ?> has been forfeited to you.</strong>
      <?php endif; ?>
    </p>
    <?php if ($forfeitReason): ?>
    <p>Reason: <?= $forfeitReason ?></p>
    <?php endif; ?>
  </div>
  <?php elseif ($isPartialRelease): ?>
  <div data-deposit-outcome="warning" role="status">
    <p>
      <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
      <?php if ($isBorrower): ?>
      <strong>$<?= $forfeitedAmount ?> of your deposit was forfeited. $<?= $refundedAmount ?> has been approved for refund.</strong>
      <?php else: ?>
      <strong>$<?= $forfeitedAmount ?> was forfeited to you. $<?= $refundedAmount ?> was refunded to the borrower.</strong>
      <?php endif; ?>
    </p>
    <?php if ($isBorrower): ?>
    <p>The refunded portion will be returned to your original payment method (<?= $provider ?>). Processing typically takes 5–10 business days.</p>
    <?php endif; ?>
    <?php if ($forfeitReason): ?>
    <p>Reason: <?= $forfeitReason ?></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <section aria-labelledby="borrow-context-heading">
    <h2 id="borrow-context-heading">
      <i class="fa-solid fa-handshake" aria-hidden="true"></i>
      Borrow Details
    </h2>

    <dl aria-label="Borrow context">
      <div>
        <dt>Tool</dt>
        <dd><a href="/tools/<?= $toolId ?>"><?= $toolName ?></a></dd>
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
        <dt>Borrow Status</dt>
        <dd data-borrow-status="<?= htmlspecialchars(strtolower($deposit['borrow_status'])) ?>"><?= $borrowStatus ?></dd>
      </div>
      <div>
        <dt>Due Date</dt>
        <dd>
          <?php if ($deposit['due_at_bor']): ?>
            <time datetime="<?= htmlspecialchars($deposit['due_at_bor']) ?>"><?= $dueDate ?></time>
          <?php else: ?>
            —
          <?php endif; ?>
        </dd>
      </div>
      <div>
        <dt>Incidents</dt>
        <dd><?= $incidentCount ?></dd>
      </div>
    </dl>
  </section>

  <?php
  $canProcess = $isAdmin
      && strtolower($deposit['deposit_status']) === 'held'
      && in_array($actionKey, ['ready for release', 'overdue - review needed', 'active borrow'], true);
  ?>
  <?php if ($canProcess): ?>
  <section aria-labelledby="process-heading">
    <h2 id="process-heading">Process Deposit</h2>
    <form method="post" action="/payments/deposit/<?= $depositId ?>" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <fieldset>
        <legend>Action</legend>
        <label>
          <input type="radio" name="action" value="release"
                 <?= !$toolReturned ? 'disabled' : 'checked' ?>>
          Release to Borrower
          <?php if (!$toolReturned): ?>
            <small>(available after tool is returned)</small>
          <?php endif; ?>
        </label>
        <label>
          <input type="radio" name="action" value="forfeit"
                 <?= !$toolReturned ? 'checked' : '' ?>>
          Forfeit to Lender
        </label>
      </fieldset>
      <fieldset>
        <legend>Forfeit Details</legend>
        <div>
          <label for="forfeit-amount">Amount ($)</label>
          <input id="forfeit-amount" name="forfeit_amount" type="number"
                 step="0.01" min="0.01" max="<?= htmlspecialchars($deposit['amount_sdp']) ?>"
                 value="<?= htmlspecialchars($old['forfeit_amount'] ?? $deposit['amount_sdp']) ?>"
                 aria-describedby="<?= isset($depositErrors['forfeit_amount']) ? 'forfeit-amount-error' : '' ?>">
          <?php if (isset($depositErrors['forfeit_amount'])): ?>
            <p id="forfeit-amount-error" role="alert"><?= htmlspecialchars($depositErrors['forfeit_amount']) ?></p>
          <?php endif; ?>
        </div>
        <div>
          <label for="forfeit-reason">Reason</label>
          <textarea id="forfeit-reason" name="reason" rows="3"
                    maxlength="2000"
                    aria-describedby="<?= isset($depositErrors['reason']) ? 'forfeit-reason-error' : '' ?>"
                    ><?= htmlspecialchars($old['reason'] ?? '') ?></textarea>
          <?php if (isset($depositErrors['reason'])): ?>
            <p id="forfeit-reason-error" role="alert"><?= htmlspecialchars($depositErrors['reason']) ?></p>
          <?php endif; ?>
        </div>
      </fieldset>
      <footer>
        <button type="submit" data-intent="success">Process Deposit</button>
      </footer>
    </form>
  </section>
  <?php endif; ?>

</section>
<?php endif; ?>
