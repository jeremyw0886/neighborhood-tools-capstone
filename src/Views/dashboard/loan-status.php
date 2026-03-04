<?php
/**
 * Dashboard — Loan detail page with lifecycle progress and actions.
 *
 * Variables from DashboardController::loanStatus():
 *   $borrow      array  Full borrow record with timestamps and counterparty info
 *   $extensions  array  Loan extension history from loan_extension_lex
 *   $handovers   array  Handover verification records
 *
 * Shared data:
 *   $authUser  array{id, name, first_name, role, avatar}
 */

$status    = $borrow['borrow_status'];
$isLender  = (int) $borrow['lender_id'] === $authUser['id'];
$stages    = ['requested', 'approved', 'borrowed', 'returned'];
$terminal  = in_array($status, ['denied', 'cancelled'], true);
$stageIndex = $terminal ? -1 : array_search($status, $stages, true);

$dueStatus = null;
if ($status === 'borrowed' && $borrow['due_at_bor'] !== null) {
    $hoursLeft = (int) ((strtotime($borrow['due_at_bor']) - time()) / 3600);
    $dueStatus = match (true) {
        $hoursLeft < 0  => 'overdue',
        $hoursLeft <= 24 => 'due-soon',
        default          => 'on-time',
    };
}
?>

<section id="loan-status" aria-labelledby="loan-status-heading">

  <header>
    <h1 id="loan-status-heading">
      <i class="fa-solid fa-timeline" aria-hidden="true"></i>
      Loan Status
    </h1>
    <p>
      <?= htmlspecialchars($borrow['tool_name_tol']) ?> &mdash;
      <?= $isLender ? 'lent to' : 'borrowed from' ?>
      <?= htmlspecialchars($isLender ? $borrow['borrower_name'] : $borrow['lender_name']) ?>
    </p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/dashboard-nav.php'; ?>

  <section aria-labelledby="lifecycle-heading">
    <h2 id="lifecycle-heading" class="visually-hidden">Lifecycle Progress</h2>

    <?php if ($terminal): ?>
      <ol aria-label="Loan lifecycle" data-terminal>
        <?php
          $terminalReached = false;
          foreach ($stages as $i => $stage):
            $label = ucfirst($stage);
            if (!$terminalReached && $stage === 'approved' && $status === 'denied'):
              $terminalReached = true;
        ?>
          <li data-completed>
            <span>Requested</span>
          </li>
          <li aria-current="step" data-status="denied">
            <span>Denied</span>
          </li>
        <?php
              break;
            elseif (!$terminalReached && $stage === 'approved' && $status === 'cancelled'):
              $terminalReached = true;
        ?>
          <li data-completed>
            <span>Requested</span>
          </li>
          <li aria-current="step" data-status="cancelled">
            <span>Cancelled</span>
          </li>
        <?php
              break;
            endif;
          endforeach;

          if (!$terminalReached && $status === 'cancelled'):
        ?>
          <li data-completed><span>Requested</span></li>
          <li data-completed><span>Approved</span></li>
          <li aria-current="step" data-status="cancelled"><span>Cancelled</span></li>
        <?php endif; ?>
      </ol>

    <?php else: ?>
      <ol aria-label="Loan lifecycle">
        <?php foreach ($stages as $i => $stage): ?>
          <?php
            $label = ucfirst($stage);
            $attrs = '';
            if ($i < $stageIndex) {
                $attrs = ' data-completed';
            } elseif ($i === $stageIndex) {
                $attrs = ' aria-current="step"';
                if ($dueStatus !== null && $stage === 'borrowed') {
                    $attrs .= match ($dueStatus) {
                        'overdue'  => ' data-urgent',
                        'due-soon' => ' data-warning',
                        default    => '',
                    };
                }
            }
          ?>
          <li<?= $attrs ?>>
            <span><?= $label ?></span>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>

  <section aria-labelledby="details-heading">
    <h2 id="details-heading">Loan Details</h2>

    <dl>
      <dt>Tool</dt>
      <dd>
        <a href="/tools/<?= (int) $borrow['id_tol_bor'] ?>">
          <?= htmlspecialchars($borrow['tool_name_tol']) ?>
        </a>
      </dd>

      <dt>Lender</dt>
      <dd>
        <a href="/profile/<?= (int) $borrow['lender_id'] ?>">
          <?= htmlspecialchars($borrow['lender_name']) ?>
        </a>
      </dd>

      <dt>Borrower</dt>
      <dd>
        <a href="/profile/<?= (int) $borrow['borrower_id'] ?>">
          <?= htmlspecialchars($borrow['borrower_name']) ?>
        </a>
      </dd>

      <dt>Status</dt>
      <dd>
        <?php if ($dueStatus): ?>
          <span data-status="<?= $dueStatus ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
        <?php elseif ($terminal): ?>
          <span data-status="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
        <?php else: ?>
          <?= htmlspecialchars(ucfirst($status)) ?>
        <?php endif; ?>
      </dd>

      <dt>Duration</dt>
      <dd><?= (int) $borrow['loan_duration_hours_bor'] ?> hours</dd>

      <dt>Requested</dt>
      <dd>
        <time datetime="<?= htmlspecialchars($borrow['requested_at_bor']) ?>">
          <?= htmlspecialchars(date('M j, Y \a\t g:ia', strtotime($borrow['requested_at_bor']))) ?>
        </time>
      </dd>

      <?php if ($borrow['approved_at_bor'] !== null): ?>
        <dt>Approved</dt>
        <dd>
          <time datetime="<?= htmlspecialchars($borrow['approved_at_bor']) ?>">
            <?= htmlspecialchars(date('M j, Y \a\t g:ia', strtotime($borrow['approved_at_bor']))) ?>
          </time>
        </dd>
      <?php endif; ?>

      <?php if ($borrow['borrowed_at_bor'] !== null): ?>
        <dt>Picked Up</dt>
        <dd>
          <time datetime="<?= htmlspecialchars($borrow['borrowed_at_bor']) ?>">
            <?= htmlspecialchars(date('M j, Y \a\t g:ia', strtotime($borrow['borrowed_at_bor']))) ?>
          </time>
        </dd>
      <?php endif; ?>

      <?php if ($borrow['due_at_bor'] !== null): ?>
        <dt>Due</dt>
        <dd>
          <time datetime="<?= htmlspecialchars($borrow['due_at_bor']) ?>">
            <?= htmlspecialchars(date('M j, Y \a\t g:ia', strtotime($borrow['due_at_bor']))) ?>
          </time>
        </dd>
      <?php endif; ?>

      <?php if ($borrow['returned_at_bor'] !== null): ?>
        <dt>Returned</dt>
        <dd>
          <time datetime="<?= htmlspecialchars($borrow['returned_at_bor']) ?>">
            <?= htmlspecialchars(date('M j, Y \a\t g:ia', strtotime($borrow['returned_at_bor']))) ?>
          </time>
        </dd>
      <?php endif; ?>

      <?php if ($borrow['notes_text_bor'] !== null && $borrow['notes_text_bor'] !== ''): ?>
        <dt>Notes</dt>
        <dd><?= nl2br(htmlspecialchars($borrow['notes_text_bor']), false) ?></dd>
      <?php endif; ?>
    </dl>
  </section>

  <?php if ($deposit !== null): ?>
  <section aria-labelledby="deposit-heading">
    <h2 id="deposit-heading">
      <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
      Security Deposit
    </h2>
    <dl>
      <dt>Status</dt>
      <dd><?= htmlspecialchars(ucfirst($deposit['deposit_status'])) ?></dd>
      <dt>Amount</dt>
      <dd>$<?= number_format((float) $deposit['amount_sdp'], 2) ?></dd>
    </dl>
    <a href="/payments/deposit/<?= (int) $deposit['id_sdp'] ?>">View Deposit Details</a>
  </section>
  <?php endif; ?>

  <?php if (!empty($extensions)): ?>
    <section aria-labelledby="extensions-heading">
      <h2 id="extensions-heading">Extension History</h2>

      <table>
        <caption class="visually-hidden">Loan extension records</caption>
        <thead>
          <tr>
            <th scope="col">Date</th>
            <th scope="col">Hours Added</th>
            <th scope="col">Previous Due</th>
            <th scope="col">New Due</th>
            <th scope="col">Reason</th>
            <th scope="col">Approved By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($extensions as $ext): ?>
            <tr>
              <td>
                <time datetime="<?= htmlspecialchars($ext['created_at_lex']) ?>">
                  <?= htmlspecialchars(date('M j, g:ia', strtotime($ext['created_at_lex']))) ?>
                </time>
              </td>
              <td><?= (int) $ext['extended_hours_lex'] ?> hrs</td>
              <td>
                <time datetime="<?= htmlspecialchars($ext['original_due_at_lex']) ?>">
                  <?= htmlspecialchars(date('M j, g:ia', strtotime($ext['original_due_at_lex']))) ?>
                </time>
              </td>
              <td>
                <time datetime="<?= htmlspecialchars($ext['new_due_at_lex']) ?>">
                  <?= htmlspecialchars(date('M j, g:ia', strtotime($ext['new_due_at_lex']))) ?>
                </time>
              </td>
              <td><?= htmlspecialchars($ext['reason_lex']) ?></td>
              <td><?= htmlspecialchars($ext['approved_by_name']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  <?php endif; ?>

  <?php
    $canApprove = $status === 'requested' && $isLender;
    $canCancel  = in_array($status, ['requested', 'approved'], true);
    $canReturn  = $status === 'borrowed' && $isLender;
    $canExtend  = $status === 'borrowed' && $isLender;
    $hasActions = $canApprove || $canCancel || $canReturn || $canExtend;
  ?>

  <?php if ($hasActions): ?>
    <section aria-labelledby="actions-heading">
      <h2 id="actions-heading">Actions</h2>

      <?php if ($canApprove): ?>
        <form method="post" action="/borrow/<?= (int) $borrow['id_bor'] ?>/approve">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <button type="submit" data-intent="success">
            <i class="fa-solid fa-check" aria-hidden="true"></i> Approve Request
          </button>
        </form>

        <details>
          <summary data-intent="danger">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i> Deny Request
          </summary>
          <form method="post" action="/borrow/<?= (int) $borrow['id_bor'] ?>/deny">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label for="deny-reason">Reason</label>
            <textarea
              id="deny-reason"
              name="reason"
              required
              maxlength="1000"
              rows="2"
              placeholder="Why are you denying this request?"
            ></textarea>
            <button type="submit" data-intent="danger">Deny Request</button>
          </form>
        </details>
      <?php endif; ?>

      <?php if ($canReturn): ?>
        <form method="post" action="/borrow/<?= (int) $borrow['id_bor'] ?>/return">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <button type="submit" data-intent="success">
            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Confirm Return
          </button>
        </form>
      <?php endif; ?>

      <?php if ($canExtend): ?>
        <details>
          <summary data-intent="warning">
            <i class="fa-solid fa-clock" aria-hidden="true"></i> Extend Loan
          </summary>
          <form method="post" action="/borrow/<?= (int) $borrow['id_bor'] ?>/extend">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label for="extra-hours">Additional hours</label>
            <input
              type="number"
              id="extra-hours"
              name="extra_hours"
              required
              min="1"
              max="720"
              placeholder="e.g. 24"
            >
            <label for="extend-reason">Reason</label>
            <textarea
              id="extend-reason"
              name="reason"
              required
              maxlength="1000"
              rows="2"
              placeholder="Why are you extending this loan?"
            ></textarea>
            <button type="submit" data-intent="warning">Extend Loan</button>
          </form>
        </details>
      <?php endif; ?>

      <?php if ($canCancel): ?>
        <details>
          <summary data-intent="danger">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel
          </summary>
          <form method="post" action="/borrow/<?= (int) $borrow['id_bor'] ?>/cancel">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label for="cancel-reason">Reason</label>
            <textarea
              id="cancel-reason"
              name="reason"
              required
              maxlength="1000"
              rows="2"
              placeholder="Why are you cancelling?"
            ></textarea>
            <button type="submit" data-intent="danger">Cancel Request</button>
          </form>
        </details>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($status === 'returned'): ?>
    <section aria-labelledby="rate-heading">
      <h2 id="rate-heading">Rate This Borrow</h2>
      <p>Share your experience to help the community.</p>
      <a href="/rate/<?= (int) $borrow['id_bor'] ?>" data-rate-cta>
        <i class="fa-solid fa-star" aria-hidden="true"></i> Leave a Rating
      </a>
    </section>
  <?php endif; ?>

</div>
</section>
