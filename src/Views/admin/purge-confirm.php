<?php
$fullName  = htmlspecialchars($account['full_name']);
$accountId = (int) $account['id_acc'];
?>

  <form method="post" action="/admin/users/<?= $accountId ?>/purge">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">

    <fieldset>
      <legend>Confirm Purge</legend>

      <p>You are about to <strong>permanently anonymize</strong> the account for
        <strong><?= $fullName ?></strong> (ID #<?= $accountId ?>).</p>

      <p>This will:</p>
      <ul>
        <li>Replace all personal information with generic placeholders</li>
        <li>Resolve all active borrows and release held deposits</li>
        <li>Dismiss open disputes and close open incidents</li>
        <li>Soft-delete all tools owned by this account</li>
        <li>Remove the avatar image from disk</li>
      </ul>

      <p><strong>This action cannot be undone.</strong></p>

      <label for="confirm-name">
        Type <strong><?= $fullName ?></strong> to confirm:
      </label>
      <input type="text"
             id="confirm-name"
             name="confirm_name"
             autocomplete="off"
             required>

      <div>
        <a href="/admin/users?<?= htmlspecialchars($returnTo) ?>" data-intent="ghost">Cancel</a>
        <button type="submit" data-intent="danger"><i class="fa-solid fa-skull-crossbones" aria-hidden="true"></i> Purge Account</button>
      </div>
    </fieldset>
  </form>
