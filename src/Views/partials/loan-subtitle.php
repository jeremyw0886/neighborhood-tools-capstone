<?php
/**
 * Loan-status subtitle — relation label, counterparty link, and status badge.
 *
 * Required keys on $loanSubtitle:
 *
 * @var array{
 *     relationLabel:    string,
 *     counterpartyId:   int,
 *     counterpartyName: string,
 *     statusLabel:      string,
 *     statusSlug:       string,
 * } $loanSubtitle
 */
?>
<p>
  <?= htmlspecialchars($loanSubtitle['relationLabel']) ?>
  <a href="/profile/<?= (int) $loanSubtitle['counterpartyId'] ?>">
    <?= htmlspecialchars($loanSubtitle['counterpartyName']) ?>
  </a>
  <span data-status="<?= htmlspecialchars($loanSubtitle['statusSlug']) ?>">
    <?= htmlspecialchars($loanSubtitle['statusLabel']) ?>
  </span>
</p>
