<?php
/**
 * File a Dispute — dispute creation form for a borrow transaction.
 *
 * Variables from DisputeController::create():
 *   $borrow       array   Row from Borrow::findById() (borrower/lender/tool context)
 *   $hasExisting  bool    Whether an open dispute already exists for this borrow
 *   $errors       array   Field-keyed validation errors (empty on first load)
 *   $old          array   Previous input values for sticky fields (empty on first load)
 *
 * Shared data:
 *   $authUser   array{id, name, first_name, role, avatar}
 *   $csrfToken  string
 *   $backUrl    string
 */

$borrowId     = (int) $borrow['id_bor'];
$toolName     = htmlspecialchars($borrow['tool_name_tol']);
$borrowerName = htmlspecialchars($borrow['borrower_name']);
$lenderName   = htmlspecialchars($borrow['lender_name']);
$borrowStatus = htmlspecialchars($borrow['borrow_status']);

$isBorrower = (int) $borrow['borrower_id'] === $authUser['id'];
$otherParty = $isBorrower ? $lenderName : $borrowerName;
$yourRole   = $isBorrower ? 'Borrower' : 'Lender';
?>

<section id="dispute-create" aria-labelledby="dispute-create-heading">

  <header>
    <h1 id="dispute-create-heading">
      <i class="fa-solid fa-gavel" aria-hidden="true"></i>
      File a Dispute
    </h1>
    <p>Report an issue with your borrow of <strong><?= $toolName ?></strong>.</p>
  </header>

  <?php if ($hasExisting): ?>

    <section aria-label="Existing dispute">
      <p role="alert" data-flash="error">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        An open dispute already exists for this borrow transaction.
        Please wait for the current dispute to be resolved before filing a new one.
      </p>
      <a href="/dashboard" role="button">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Dashboard
      </a>
    </section>

  <?php else: ?>

    <?php if (!empty($errors)): ?>
      <ul role="alert" aria-label="Form errors">
        <?php foreach ($errors as $msg): ?>
          <li><?= htmlspecialchars($msg) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <dl aria-label="Borrow details">
      <div>
        <dt>Tool</dt>
        <dd><?= $toolName ?></dd>
      </div>
      <div>
        <dt>Your Role</dt>
        <dd><?= $yourRole ?></dd>
      </div>
      <div>
        <dt>Other Party</dt>
        <dd><?= $otherParty ?></dd>
      </div>
      <div>
        <dt>Borrow Status</dt>
        <dd><?= $borrowStatus ?></dd>
      </div>
    </dl>

    <form action="/disputes" method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="borrow_id" value="<?= $borrowId ?>">

      <fieldset>
        <legend>Dispute Details</legend>

        <div>
          <label for="dispute-subject">Subject <span aria-hidden="true">*</span></label>
          <input type="text"
                 id="dispute-subject"
                 name="subject"
                 required
                 maxlength="255"
                 autocomplete="off"
                 placeholder="Brief description of the issue"
                 value="<?= htmlspecialchars($old['subject'] ?? '') ?>"
                 <?php if (isset($errors['subject'])): ?>aria-invalid="true" aria-describedby="subject-error"<?php endif; ?>>
          <?php if (isset($errors['subject'])): ?>
            <p id="subject-error" role="alert"><?= htmlspecialchars($errors['subject']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="dispute-message">Describe the Issue <span aria-hidden="true">*</span></label>
          <textarea id="dispute-message"
                    name="message"
                    required
                    rows="6"
                    maxlength="5000"
                    placeholder="Explain what happened, when it occurred, and what resolution you're seeking."
                    <?php if (isset($errors['message'])): ?>aria-invalid="true" aria-describedby="message-error"<?php endif; ?>><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
          <?php if (isset($errors['message'])): ?>
            <p id="message-error" role="alert"><?= htmlspecialchars($errors['message']) ?></p>
          <?php endif; ?>
        </div>
      </fieldset>

      <footer>
        <button type="submit">
          <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit Dispute
        </button>
        <a href="/dashboard">Cancel</a>
      </footer>
    </form>

  <?php endif; ?>

</section>
