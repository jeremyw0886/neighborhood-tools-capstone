<?php
/**
 * Deposit Detail — security deposit status, amount, and borrow context.
 *
 * Variables from PaymentController::deposit():
 *   $deposit   array   Row from Deposit::findById()
 *   $isAdmin   bool    Whether the current user is an admin
 *
 * Shared data:
 *   $authUser   array{id, name, first_name, role, avatar}
 *   $csrfToken  string
 */

$depositId     = (int) $deposit['id_sdp'];
$amount        = number_format((float) $deposit['amount_sdp'], 2);
$status        = htmlspecialchars($deposit['deposit_status']);
$action        = htmlspecialchars($deposit['action_required']);
$actionKey     = strtolower($deposit['action_required']);
$daysHeld      = (int) $deposit['days_held'];
$provider      = htmlspecialchars($deposit['payment_provider']);
$heldDate      = date('M j, Y \a\t g:i A', strtotime($deposit['held_at_sdp']));
$toolName      = htmlspecialchars($deposit['tool_name_tol']);
$toolId        = (int) $deposit['id_tol'];
$borrowerName  = htmlspecialchars($deposit['borrower_name']);
$lenderName    = htmlspecialchars($deposit['lender_name']);
$borrowStatus  = htmlspecialchars($deposit['borrow_status']);
$dueDate       = $deposit['due_at_bor']
    ? date('M j, Y \a\t g:i A', strtotime($deposit['due_at_bor']))
    : '—';
$incidentCount = (int) $deposit['incident_count'];
$estimatedVal  = number_format((float) $deposit['estimated_value_tol'], 2);
?>

<section id="deposit-detail" aria-labelledby="deposit-heading">

  <nav aria-label="Back">
    <?php if ($isAdmin): ?>
      <a href="/admin">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Admin Dashboard
      </a>
    <?php else: ?>
      <a href="/dashboard">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Dashboard
      </a>
    <?php endif; ?>
  </nav>

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
    <div>
      <dt>Status</dt>
      <dd><?= $status ?></dd>
    </div>
    <div>
      <dt>Days Held</dt>
      <dd><?= $daysHeld ?> day<?= $daysHeld !== 1 ? 's' : '' ?></dd>
    </div>
    <div>
      <dt>Provider</dt>
      <dd><?= $provider ?></dd>
    </div>
    <div>
      <dt>Held Since</dt>
      <dd><time datetime="<?= htmlspecialchars($deposit['held_at_sdp']) ?>"><?= $heldDate ?></time></dd>
    </div>
    <div>
      <dt>Tool Value</dt>
      <dd>$<?= $estimatedVal ?></dd>
    </div>
  </dl>

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

</section>
