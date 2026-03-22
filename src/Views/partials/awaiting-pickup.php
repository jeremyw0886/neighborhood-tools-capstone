<?php
/**
 * Shared awaiting-pickup section for lender and borrower dashboards.
 *
 * @var array  $awaitingPickup     Approved borrows awaiting pickup
 * @var string $pickupRole         'lender' | 'borrower'
 * @var array  $depositsByBorrow   Keyed by borrow ID — deposit rows
 * @var array  $handoversByBorrow  Keyed by borrow ID — pending handover rows
 * @var array  $waiversByBorrow    Keyed by borrow ID — true if waiver signed
 */
?>
<section aria-labelledby="awaiting-pickup-heading">
  <h2 id="awaiting-pickup-heading">
    <i class="fa-solid fa-box-open" aria-hidden="true"></i>
    Awaiting Pickup (<?= count($awaitingPickup) ?>)
  </h2>

  <ul data-card-list>
    <?php foreach ($awaitingPickup as $pickup): ?>
      <?php
        $pickupId      = (int) $pickup['id_bor'];
        $waiverSigned  = $waiversByBorrow[$pickupId] ?? false;
        $deposit       = $depositsByBorrow[$pickupId] ?? null;
        $handover      = $handoversByBorrow[$pickupId] ?? null;
        $depositPaid   = $deposit === null || $deposit['deposit_status'] !== 'pending';
        $approvedHoursAgo = (int) ((time() - strtotime($pickup['approved_at_bor'])) / 3600);
        $approvedDaysAgo  = (int) floor($approvedHoursAgo / 24);
        $pickupUrgency    = $approvedDaysAgo >= 3 ? 'overdue' : ($approvedDaysAgo >= 2 ? 'due-soon' : null);
        $approvedAgoLabel = $approvedDaysAgo > 0
          ? $approvedDaysAgo . ' day' . ($approvedDaysAgo !== 1 ? 's' : '') . ' ago'
          : $approvedHoursAgo . 'h ago';
      ?>
      <li>
        <article data-activity-card>
          <header>
            <a href="/dashboard/loan/<?= $pickupId ?>">
              <?= htmlspecialchars($pickup['tool_name_tol']) ?>
            </a>
            <span data-status="approved"><?= $pickupRole === 'lender' ? 'Awaiting Pickup' : 'Ready for Pickup' ?></span>
          </header>
          <dl>
            <?php if ($pickupRole === 'lender'): ?>
              <dt>Borrower</dt>
              <dd>
                <a href="/profile/<?= (int) $pickup['borrower_id'] ?>">
                  <?= htmlspecialchars($pickup['borrower_name']) ?>
                </a>
              </dd>
            <?php else: ?>
              <dt>Lender</dt>
              <dd>
                <a href="/profile/<?= (int) $pickup['lender_id'] ?>">
                  <?= htmlspecialchars($pickup['lender_name']) ?>
                </a>
              </dd>
            <?php endif; ?>
            <dt>Approved</dt>
            <dd>
              <time datetime="<?= htmlspecialchars($pickup['approved_at_bor']) ?>">
                <?= htmlspecialchars(date('M j, g:ia', strtotime($pickup['approved_at_bor']))) ?>
              </time>
              <small<?= $pickupUrgency !== null ? ' data-status="' . $pickupUrgency . '"' : '' ?>><?= htmlspecialchars($approvedAgoLabel) ?></small>
            </dd>
            <dt>Duration</dt>
            <dd><?= htmlspecialchars(\App\Core\ViewHelper::formatDuration((int) $pickup['loan_duration_hours_bor'])) ?></dd>
            <?php if ($pickupRole === 'lender' && $deposit !== null): ?>
            <dt>Deposit</dt>
            <dd>
              <a href="/payments/deposit/<?= (int) $deposit['id_sdp'] ?>">
                $<?= number_format((float) $deposit['amount_sdp'], 2) ?>
              </a>
              <small>(<?= htmlspecialchars(str_replace('_', ' ', $deposit['deposit_status'])) ?>)</small>
            </dd>
            <?php endif; ?>
          </dl>
          <footer data-actions>
            <?php if ($pickupRole === 'lender'): ?>
              <?php if (!$waiverSigned): ?>
                <span role="button" aria-disabled="true" data-intent="ghost">
                  <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Awaiting Waiver
                </span>
              <?php elseif (!$depositPaid): ?>
                <span role="button" aria-disabled="true" data-intent="ghost">
                  <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Awaiting Deposit
                </span>
              <?php elseif ($handover !== null): ?>
                <a href="/handover/<?= $pickupId ?>" role="button" data-intent="info">
                  <i class="fa-solid fa-key" aria-hidden="true"></i> Your Pickup Code
                </a>
              <?php else: ?>
                <a href="/handover/<?= $pickupId ?>" role="button" data-intent="info">
                  <i class="fa-solid fa-key" aria-hidden="true"></i> Generate Pickup Code
                </a>
              <?php endif; ?>
            <?php else: ?>
              <?php if (!$waiverSigned): ?>
                <a href="/waiver/<?= $pickupId ?>" role="button" data-intent="warning">
                  <i class="fa-solid fa-file-signature" aria-hidden="true"></i> Sign Waiver
                </a>
              <?php elseif (!$depositPaid): ?>
                <a href="/payments/deposit/<?= (int) $deposit['id_sdp'] ?>" role="button" data-intent="warning">
                  <i class="fa-solid fa-credit-card" aria-hidden="true"></i> Pay Deposit
                </a>
              <?php elseif ($handover !== null): ?>
                <a href="/handover/<?= $pickupId ?>" role="button" data-intent="info">
                  <i class="fa-solid fa-keyboard" aria-hidden="true"></i> Enter Pickup Code
                </a>
              <?php else: ?>
                <span role="button" aria-disabled="true" data-intent="ghost">
                  <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Awaiting Pickup Code
                </span>
              <?php endif; ?>
            <?php endif; ?>
            <details>
              <summary data-intent="danger">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel
              </summary>
              <form method="post" action="/borrow/<?= $pickupId ?>/cancel">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <label for="cancel-reason-<?= $pickupId ?>">Reason</label>
                <textarea
                  id="cancel-reason-<?= $pickupId ?>"
                  name="reason"
                  required
                  maxlength="1000"
                  rows="2"
                  placeholder="<?= $pickupRole === 'lender' ? 'Why are you cancelling this pickup?' : 'Why are you cancelling?' ?>"
                ></textarea>
                <button type="submit" data-intent="danger"><?= $pickupRole === 'lender' ? 'Cancel Pickup' : 'Cancel Request' ?></button>
              </form>
            </details>
          </footer>
        </article>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
