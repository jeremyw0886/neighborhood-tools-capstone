<?php

/**
 * Dashboard — Loan detail page with lifecycle progress and actions.
 *
 * Variables from DashboardController::loanStatus():
 *   $borrow        array  Full borrow record with timestamps and counterparty info
 *   $extensions    array  Loan extension history from loan_extension_lex
 *   $handovers     array  Handover verification records
 *   $deposit       ?array Security deposit record
 *   $waiverSigned  bool   Whether the borrower has signed the waiver
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
$timeLabel = null;
if ($status === 'borrowed' && $borrow['due_at_bor'] !== null) {
  $secondsLeft = strtotime($borrow['due_at_bor']) - time();
  $hoursLeft   = (int) ($secondsLeft / 3600);
  $dueStatus   = match (true) {
    $hoursLeft < 0   => 'overdue',
    $hoursLeft <= 24 => 'due-soon',
    default          => 'on-time',
  };

  $absHours = abs($hoursLeft);
  $timeLabel = match (true) {
    $absHours >= 24 => (int) floor($absHours / 24) . 'd ' . ($absHours % 24) . 'h' . ($hoursLeft < 0 ? ' overdue' : ' left'),
    $absHours > 0   => $absHours . 'h' . ($hoursLeft < 0 ? ' overdue' : ' left'),
    default         => 'Due now',
  };
}

$statusLabel = match ($status) {
  'requested' => 'Pending',
  'approved'  => 'Approved',
  'borrowed'  => 'Borrowed',
  'returned'  => 'Returned',
  'denied'    => 'Denied',
  'cancelled' => 'Cancelled',
};
$statusSlug = $dueStatus ?? $status;
$counterpartyLabel = $isLender ? 'Borrower' : 'Lender';
$counterpartyName  = $isLender ? $borrow['borrower_name'] : $borrow['lender_name'];
$counterpartyId    = $isLender ? (int) $borrow['borrower_id'] : (int) $borrow['lender_id'];
?>

<?php if (!empty($handoverSuccess)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($handoverSuccess) ?></p>
  <?php endif; ?>
  <?php if (!empty($borrowSuccess)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($borrowSuccess) ?></p>
  <?php endif; ?>
  <?php if (!empty($waiverSuccess)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($waiverSuccess) ?></p>
  <?php endif; ?>
  <?php if (!empty($depositSuccess)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($depositSuccess) ?></p>
  <?php endif; ?>
  <?php if (!empty($ratingSuccess)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($ratingSuccess) ?></p>
  <?php endif; ?>

  <?php
    $flashError = $borrowErrors['general']
      ?? $borrowErrors['reason']
      ?? $borrowErrors['extra_hours']
      ?? '';
    if ($flashError !== ''):
  ?>
    <p role="alert" data-flash="error"><?= htmlspecialchars($flashError) ?></p>
  <?php endif; ?>

  <?php if (!empty($decisionData)): ?>
    <script id="decision-data" type="application/json"><?= json_encode($decisionData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR) ?></script>
  <?php endif; ?>

  <div data-loan-body>

    <section aria-labelledby="lifecycle-heading" data-loan-card>
      <h2 id="lifecycle-heading">
        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
        Lifecycle
      </h2>

      <?php if ($terminal): ?>
        <ol aria-label="Loan lifecycle" data-terminal>
          <?php if ($status === 'denied'): ?>
            <li data-completed><span>Requested</span></li>
            <li aria-current="step" data-status="denied"><span>Denied</span></li>
          <?php elseif ($status === 'cancelled'): ?>
            <?php
            $cancelledAfterApproval = $borrow['approved_at_bor'] !== null;
            ?>
            <li data-completed><span>Requested</span></li>
            <?php if ($cancelledAfterApproval): ?>
              <li data-completed><span>Approved</span></li>
            <?php endif; ?>
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

      <?php if ($timeLabel !== null): ?>
        <p data-time-remaining data-status="<?= htmlspecialchars($dueStatus) ?>">
          <i class="fa-solid fa-clock" aria-hidden="true"></i>
          <?= htmlspecialchars($timeLabel) ?>
        </p>
      <?php endif; ?>
    </section>

    <?php
    $nextStep = null;

    if (!$terminal) {
        $nsDepositPaid    = $deposit === null || $deposit['deposit_status'] !== 'pending';
        $nsPickupHandover = array_find($handovers, static fn(array $h): bool => $h['handover_type'] === 'pickup');
        $nsReturnHandover = array_find($handovers, static fn(array $h): bool => $h['handover_type'] === 'return' && $h['verified_at_hov'] === null);

        if ($isLender) {
            $nextStep = match (true) {
                $status === 'requested' => [
                    'message' => 'Review this borrow request',
                    'icon'    => 'fa-magnifying-glass',
                    'url'     => '#actions-heading',
                    'label'   => 'Review Request',
                ],
                $status === 'approved' && !$waiverSigned => [
                    'message' => 'Waiting for the borrower to sign the waiver',
                    'icon'    => 'fa-hourglass-half',
                ],
                $status === 'approved' && !$nsDepositPaid => [
                    'message' => 'Waiting for the borrower to pay the security deposit',
                    'icon'    => 'fa-hourglass-half',
                ],
                $status === 'approved' && $nsPickupHandover !== null => [
                    'message' => 'Pickup code generated — waiting for the borrower to verify',
                    'icon'    => 'fa-hourglass-half',
                ],
                $status === 'approved' => [
                    'message' => 'Generate a pickup code to start the handover',
                    'icon'    => 'fa-key',
                    'url'     => '/handover/' . (int) $borrow['id_bor'],
                    'label'   => 'Generate Pickup Code',
                ],
                $status === 'borrowed' && $nsReturnHandover !== null => [
                    'message' => 'Enter the return code to confirm the return',
                    'icon'    => 'fa-keyboard',
                    'url'     => '/handover/' . (int) $borrow['id_bor'],
                    'label'   => 'Enter Return Code',
                ],
                $status === 'borrowed' => [
                    'message' => 'Waiting for the borrower to initiate return',
                    'icon'    => 'fa-hourglass-half',
                ],
                $status === 'returned' && !$hasRatedUser => [
                    'message' => 'Rate your experience with this borrow',
                    'icon'    => 'fa-star',
                    'url'     => '/rate/' . (int) $borrow['id_bor'],
                    'label'   => 'Rate Experience',
                ],
                default => null,
            };
        } else {
            $nextStep = match (true) {
                $status === 'requested' => [
                    'message' => 'Waiting for the lender to review your request',
                    'icon'    => 'fa-hourglass-half',
                ],
                $status === 'approved' && !$waiverSigned => [
                    'message' => 'Sign the borrow waiver to continue',
                    'icon'    => 'fa-file-signature',
                    'url'     => '/waiver/' . (int) $borrow['id_bor'],
                    'label'   => 'Sign Waiver',
                ],
                $status === 'approved' && !$nsDepositPaid => [
                    'message' => 'Pay the security deposit to continue',
                    'icon'    => 'fa-credit-card',
                    'url'     => '/payments/deposit/' . (int) $deposit['id_sdp'],
                    'label'   => 'Pay Deposit',
                ],
                $status === 'approved' && $nsPickupHandover === null => [
                    'message' => 'Waiting for the lender to generate a pickup code',
                    'icon'    => 'fa-hourglass-half',
                ],
                $status === 'approved' => [
                    'message' => 'Enter the pickup code to complete the handover',
                    'icon'    => 'fa-keyboard',
                    'url'     => '/handover/' . (int) $borrow['id_bor'],
                    'label'   => 'Enter Pickup Code',
                ],
                $status === 'borrowed' && $nsReturnHandover !== null => [
                    'message' => 'Share the return code with the lender',
                    'icon'    => 'fa-key',
                    'url'     => '/handover/' . (int) $borrow['id_bor'],
                    'label'   => 'View Return Code',
                ],
                $status === 'borrowed' => [
                    'message' => 'Ready to return? Generate a return code',
                    'icon'    => 'fa-key',
                    'url'     => '/handover/' . (int) $borrow['id_bor'],
                    'label'   => 'Generate Return Code',
                ],
                $status === 'returned' && (!$hasRatedUser || !$hasRatedTool) => [
                    'message' => 'Rate your experience with this borrow',
                    'icon'    => 'fa-star',
                    'url'     => '/rate/' . (int) $borrow['id_bor'],
                    'label'   => 'Rate Experience',
                ],
                default => null,
            };
        }
    }
    ?>

    <?php if ($nextStep !== null): ?>
      <section aria-labelledby="next-step-heading" data-loan-card data-next-step>
        <h2 id="next-step-heading">
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          Next Step
        </h2>
        <p>
          <i class="fa-solid <?= $nextStep['icon'] ?>" aria-hidden="true"></i>
          <?= htmlspecialchars($nextStep['message']) ?>
        </p>
        <?php if (isset($nextStep['url'])): ?>
          <a href="<?= htmlspecialchars($nextStep['url']) ?>" data-intent="primary" role="button">
            <?= htmlspecialchars($nextStep['label']) ?>
          </a>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if ($status === 'approved' && !$isLender): ?>
      <section aria-labelledby="pickup-checklist-heading" data-loan-card>
        <h2 id="pickup-checklist-heading">
          <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
          Pickup Checklist
        </h2>
        <?php
        $depositPaid    = $deposit === null || $deposit['deposit_status'] !== 'pending';
        $pickupHandover = array_find($handovers, static fn(array $h): bool => $h['handover_type'] === 'pickup');
        $hasPickupCode  = $pickupHandover !== null;
        ?>
        <ul data-checklist>
          <li<?= $waiverSigned ? ' data-done' : '' ?>>
            <i class="fa-solid <?= $waiverSigned ? 'fa-circle-check' : 'fa-circle' ?>" aria-hidden="true"></i>
            <?php if ($waiverSigned): ?>
              <span>Waiver signed</span>
            <?php else: ?>
              <a href="/waiver/<?= (int) $borrow['id_bor'] ?>">Sign waiver</a>
            <?php endif; ?>
            </li>
            <?php if ($deposit !== null): ?>
              <li<?= $depositPaid ? ' data-done' : '' ?>>
                <i class="fa-solid <?= $depositPaid ? 'fa-circle-check' : 'fa-circle' ?>" aria-hidden="true"></i>
                <?php if ($depositPaid): ?>
                  <span>Deposit paid</span>
                <?php else: ?>
                  <a href="/payments/deposit/<?= (int) $deposit['id_sdp'] ?>">Pay deposit ($<?= number_format((float) $deposit['amount_sdp'], 2) ?>)</a>
                <?php endif; ?>
                </li>
              <?php endif; ?>
              <li<?= $hasPickupCode ? ' data-done' : '' ?>>
                <i class="fa-solid <?= $hasPickupCode ? 'fa-circle-check' : 'fa-circle' ?>" aria-hidden="true"></i>
                <?php if ($hasPickupCode): ?>
                  <span>Handover code ready</span>
                <?php else: ?>
                  <span>Awaiting handover code from lender</span>
                <?php endif; ?>
                </li>
        </ul>
        <?php if ($waiverSigned && $depositPaid && $hasPickupCode): ?>
          <a href="/handover/<?= (int) $borrow['id_bor'] ?>" data-intent="primary" role="button">
            <i class="fa-solid fa-keyboard" aria-hidden="true"></i> Enter Pickup Code
          </a>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if ($status === 'approved' && $isLender): ?>
      <?php
      $pickupHandover = array_find($handovers, static fn(array $h): bool => $h['handover_type'] === 'pickup');
      $hasPickupCode  = $pickupHandover !== null;
      ?>
      <section aria-labelledby="pickup-checklist-heading" data-loan-card>
        <h2 id="pickup-checklist-heading">
          <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
          Pickup Checklist
        </h2>
        <ul data-checklist>
          <li<?= $waiverSigned ? ' data-done' : '' ?>>
            <i class="fa-solid <?= $waiverSigned ? 'fa-circle-check' : 'fa-circle' ?>" aria-hidden="true"></i>
            <span>Borrower <?= $waiverSigned ? 'signed' : 'needs to sign' ?> waiver</span>
            </li>
            <?php if ($deposit !== null): ?>
              <?php $depositPaid = $deposit['deposit_status'] !== 'pending'; ?>
              <li<?= $depositPaid ? ' data-done' : '' ?>>
                <i class="fa-solid <?= $depositPaid ? 'fa-circle-check' : 'fa-circle' ?>" aria-hidden="true"></i>
                <span>Deposit <?= $depositPaid ? 'paid' : 'pending' ?></span>
                </li>
              <?php endif; ?>
              <li<?= $hasPickupCode ? ' data-done' : '' ?>>
                <i class="fa-solid <?= $hasPickupCode ? 'fa-circle-check' : 'fa-circle' ?>" aria-hidden="true"></i>
                <?php if ($hasPickupCode): ?>
                  <span>Pickup code generated</span>
                <?php else: ?>
                  <a href="/handover/<?= (int) $borrow['id_bor'] ?>">Generate pickup code</a>
                <?php endif; ?>
                </li>
        </ul>
      </section>
    <?php endif; ?>

    <section aria-labelledby="details-heading" data-loan-card>
      <h2 id="details-heading">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        Loan Details
      </h2>

      <dl>
        <dt>Tool</dt>
        <dd>
          <a href="/tools/<?= (int) $borrow['id_tol_bor'] ?>">
            <?= htmlspecialchars($borrow['tool_name_tol']) ?>
          </a>
        </dd>

        <dt><?= $counterpartyLabel ?></dt>
        <dd>
          <a href="/profile/<?= $counterpartyId ?>">
            <?= htmlspecialchars($counterpartyName) ?>
          </a>
        </dd>

        <dt>Duration</dt>
        <dd><?= htmlspecialchars(\App\Core\ViewHelper::formatDuration((int) $borrow['loan_duration_hours_bor'])) ?></dd>

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
            <?php if ($timeLabel !== null): ?>
              <small>(<?= htmlspecialchars($timeLabel) ?>)</small>
            <?php endif; ?>
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
      <section aria-labelledby="deposit-heading" data-loan-card>
        <h2 id="deposit-heading">
          <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
          Security Deposit
        </h2>
        <dl>
          <dt>Status</dt>
          <dd>
            <span data-status="<?= htmlspecialchars($deposit['deposit_status']) ?>">
              <?= htmlspecialchars(ucfirst($deposit['deposit_status'])) ?>
            </span>
          </dd>
          <dt>Amount</dt>
          <dd>$<?= number_format((float) $deposit['amount_sdp'], 2) ?></dd>
          <?php if ($deposit['payment_provider'] !== null): ?>
            <dt>Provider</dt>
            <dd><?= htmlspecialchars(ucfirst($deposit['payment_provider'])) ?></dd>
          <?php endif; ?>
        </dl>
        <a href="/payments/deposit/<?= (int) $deposit['id_sdp'] ?>">View Deposit Details</a>
      </section>
    <?php endif; ?>

    <?php if (!empty($handovers)): ?>
      <section aria-labelledby="handovers-heading" data-loan-card>
        <h2 id="handovers-heading">
          <i class="fa-solid fa-handshake" aria-hidden="true"></i>
          Handover Verification
        </h2>
        <ul data-handover-list>
          <?php foreach ($handovers as $hov): ?>
            <li>
              <div data-handover-record>
                <header>
                  <span data-handover-type><?= htmlspecialchars(ucfirst($hov['handover_type'])) ?></span>
                  <?php if ($hov['verified_at_hov'] !== null): ?>
                    <span data-status="on-time">Verified</span>
                  <?php else: ?>
                    <span data-status="requested">Pending</span>
                  <?php endif; ?>
                </header>
                <dl>
                  <dt>Generated by</dt>
                  <dd><?= htmlspecialchars($hov['generator_name']) ?></dd>
                  <dt>Generated</dt>
                  <dd>
                    <time datetime="<?= htmlspecialchars($hov['generated_at_hov']) ?>">
                      <?= htmlspecialchars(date('M j, g:ia', strtotime($hov['generated_at_hov']))) ?>
                    </time>
                  </dd>
                  <?php if ($hov['verified_at_hov'] !== null): ?>
                    <dt>Verified by</dt>
                    <dd><?= htmlspecialchars($hov['verifier_name'] ?? 'N/A') ?></dd>
                    <dt>Verified</dt>
                    <dd>
                      <time datetime="<?= htmlspecialchars($hov['verified_at_hov']) ?>">
                        <?= htmlspecialchars(date('M j, g:ia', strtotime($hov['verified_at_hov']))) ?>
                      </time>
                    </dd>
                  <?php endif; ?>
                  <?php if ($hov['condition_notes_hov'] !== null && $hov['condition_notes_hov'] !== ''): ?>
                    <dt>Condition</dt>
                    <dd><?= htmlspecialchars($hov['condition_notes_hov']) ?></dd>
                  <?php endif; ?>
                </dl>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php
        $pendingHandover = array_find($handovers, static fn(array $h): bool => $h['verified_at_hov'] === null);
        ?>
        <?php if ($pendingHandover !== null): ?>
          <a href="/handover/<?= (int) $borrow['id_bor'] ?>" data-intent="primary" role="button">
            <i class="fa-solid fa-keyboard" aria-hidden="true"></i>
            <?= htmlspecialchars(ucfirst($pendingHandover['handover_type'])) ?> Verification
          </a>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if (!empty($extensions)): ?>
      <section aria-labelledby="extensions-heading" data-loan-card>
        <h2 id="extensions-heading">
          <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
          Extension History
        </h2>

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
    $canApprove  = $status === 'requested' && $isLender;
    $canCancel   = in_array($status, ['requested', 'approved'], true);
    $canReturn   = $status === 'borrowed' && $isLender;
    $canExtend   = $status === 'borrowed' && $isLender;
    $canRemind   = $status === 'borrowed' && $isLender;
    $hasActions  = $canApprove || $canCancel || $canReturn || $canExtend || $canRemind;
    ?>

    <?php if ($hasActions): ?>
      <section aria-labelledby="actions-heading" data-loan-card data-actions-card>
        <h2 id="actions-heading">
          <i class="fa-solid fa-bolt" aria-hidden="true"></i>
          Actions
        </h2>

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
                placeholder="Why are you denying this request?"></textarea>
              <button type="submit" data-intent="danger"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Deny Request</button>
            </form>
          </details>
        <?php endif; ?>

        <?php if ($canReturn): ?>
          <?php
          $returnHandover = array_find($handovers, static fn(array $h): bool => $h['handover_type'] === 'return');
          $hasReturnCode  = $returnHandover !== null;
          ?>
          <?php if ($hasReturnCode): ?>
            <a href="/handover/<?= (int) $borrow['id_bor'] ?>" role="button" data-intent="info">
              <i class="fa-solid fa-keyboard" aria-hidden="true"></i> Enter Return Code
            </a>
          <?php else: ?>
            <span role="button" aria-disabled="true" data-intent="ghost">
              <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Awaiting Return Code
            </span>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($status === 'borrowed' && !$isLender): ?>
          <?php
          $returnHandover = array_find($handovers, static fn(array $h): bool => $h['handover_type'] === 'return' && $h['verified_at_hov'] === null);
          $hasReturnCode  = $returnHandover !== null;
          ?>
          <?php if ($hasReturnCode): ?>
            <a href="/handover/<?= (int) $borrow['id_bor'] ?>" role="button" data-intent="info">
              <i class="fa-solid fa-key" aria-hidden="true"></i> Your Return Code
            </a>
          <?php else: ?>
            <a href="/handover/<?= (int) $borrow['id_bor'] ?>" role="button" data-intent="info">
              <i class="fa-solid fa-key" aria-hidden="true"></i> Generate Return Code
            </a>
          <?php endif; ?>
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
                placeholder="e.g. 24">
              <label for="extend-reason">Reason</label>
              <textarea
                id="extend-reason"
                name="reason"
                required
                maxlength="1000"
                rows="2"
                placeholder="Why are you extending this loan?"></textarea>
              <button type="submit" data-intent="warning">Extend Loan</button>
            </form>
          </details>
        <?php endif; ?>

        <?php if ($canRemind): ?>
          <form method="post" action="/borrow/<?= (int) $borrow['id_bor'] ?>/remind">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <button type="submit" data-intent="info">
              <i class="fa-solid fa-bell" aria-hidden="true"></i> Send Reminder
            </button>
          </form>
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
                placeholder="Why are you cancelling?"></textarea>
              <button type="submit" data-intent="danger"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel Request</button>
            </form>
          </details>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if ($status === 'returned'): ?>
      <?php
      $needsUserRating = !$hasRatedUser;
      $needsToolRating = !$isLender && !$hasRatedTool;
      $showRateSection = $needsUserRating || $needsToolRating;
      ?>
      <?php if ($showRateSection): ?>
        <section aria-labelledby="rate-heading" data-loan-card>
          <h2 id="rate-heading">
            <i class="fa-solid fa-star" aria-hidden="true"></i>
            Rate This Experience
          </h2>
          <?php if ($needsUserRating): ?>
            <a href="/rate/<?= (int) $borrow['id_bor'] ?>" data-rate-cta>
              <i class="fa-solid fa-star" aria-hidden="true"></i>
              Rate <?= htmlspecialchars($counterpartyName) ?>
            </a>
          <?php endif; ?>
          <?php if ($needsToolRating): ?>
            <a href="/rate/<?= (int) $borrow['id_bor'] ?>" data-rate-cta>
              <i class="fa-solid fa-wrench" aria-hidden="true"></i>
              Rate <?= htmlspecialchars($borrow['tool_name_tol']) ?>
            </a>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if (!empty($userRatings) || $toolRating !== null): ?>
        <section aria-labelledby="ratings-heading" data-loan-card>
          <h2 id="ratings-heading">
            <i class="fa-solid fa-star-half-stroke" aria-hidden="true"></i>
            Ratings
          </h2>
          <?php foreach ($userRatings as $ur): ?>
            <?php
            $isOwnRating = (int) $ur['rater_id'] === $authUser['id'];
            $ratingLabel  = $isOwnRating
              ? 'You rated ' . htmlspecialchars($counterpartyName)
              : htmlspecialchars($ur['rater_name']) . ' rated you';
            ?>
            <article data-rating-card>
              <h3><?= $ratingLabel ?> <span>as <?= htmlspecialchars(ucfirst($ur['role'])) ?></span></h3>
              <div role="img" aria-label="<?= (int) $ur['score'] ?> out of 5 stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="fa-<?= $i <= (int) $ur['score'] ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
                <?php endfor; ?>
              </div>
              <?php if ($ur['review'] !== null && $ur['review'] !== ''): ?>
                <blockquote><?= htmlspecialchars($ur['review']) ?></blockquote>
              <?php endif; ?>
              <time datetime="<?= htmlspecialchars($ur['created_at']) ?>">
                <?= htmlspecialchars(date('M j, Y', strtotime($ur['created_at']))) ?>
              </time>
            </article>
          <?php endforeach; ?>

          <?php if ($toolRating !== null): ?>
            <article data-rating-card>
              <h3>
                <?= $isLender
                  ? htmlspecialchars($toolRating['rater_name']) . ' rated '
                  : 'You rated ' ?>
                <?= htmlspecialchars($toolRating['tool_name']) ?>
              </h3>
              <div role="img" aria-label="<?= (int) $toolRating['score'] ?> out of 5 stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="fa-<?= $i <= (int) $toolRating['score'] ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
                <?php endfor; ?>
              </div>
              <?php if ($toolRating['review'] !== null && $toolRating['review'] !== ''): ?>
                <blockquote><?= htmlspecialchars($toolRating['review']) ?></blockquote>
              <?php endif; ?>
              <time datetime="<?= htmlspecialchars($toolRating['created_at']) ?>">
                <?= htmlspecialchars(date('M j, Y', strtotime($toolRating['created_at']))) ?>
              </time>
            </article>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    <?php endif; ?>

  </div>