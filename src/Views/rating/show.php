<?php
/**
 * Rating form — rate the other party and (for borrowers) the tool.
 *
 * Variables from RatingController::show():
 *   $borrow       array  Row from Borrow::findById() (id_bor, borrower_id, lender_id, tool_name_tol, etc.)
 *   $isBorrower   bool   Whether the current user is the borrower
 *   $targetId     int    Account ID of the user being rated
 *   $targetName   string Full name of the user being rated
 *   $raterRole    string 'lender' or 'borrower'
 *   $hasRatedUser bool   Whether the user rating has already been submitted
 *   $hasRatedTool bool   Whether the tool rating has already been submitted (always true for lenders)
 *
 * Shared data:
 *   $csrfToken  string
 */

$toolName = htmlspecialchars($borrow['tool_name_tol']);
$borrowId = (int) $borrow['id_bor'];

$old = $_SESSION['rating_old'] ?? [];
unset($_SESSION['rating_old']);

$scoreLabels = [
    1 => 'Poor',
    2 => 'Fair',
    3 => 'Good',
    4 => 'Very Good',
    5 => 'Excellent',
];
?>

<section id="rating" aria-labelledby="rating-heading">

  <header>
    <h1 id="rating-heading">
      <i class="fa-solid fa-star" aria-hidden="true"></i>
      Rate Your Experience
    </h1>
    <p>Leave your rating for the borrow of <strong><?= $toolName ?></strong>.</p>
  </header>

  <?php if (!empty($_SESSION['rating_success'])): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($_SESSION['rating_success']) ?></p>
    <?php unset($_SESSION['rating_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['rating_errors']['general'])): ?>
    <p role="alert" data-flash="error"><?= htmlspecialchars($_SESSION['rating_errors']['general']) ?></p>
    <?php unset($_SESSION['rating_errors']); ?>
  <?php endif; ?>

  <dl aria-label="Borrow details">
    <div>
      <dt>Tool</dt>
      <dd><?= $toolName ?></dd>
    </div>
    <div>
      <dt>Lender</dt>
      <dd>
        <a href="/profile/<?= (int) $borrow['lender_id'] ?>">
          <?= htmlspecialchars($borrow['lender_name']) ?>
        </a>
      </dd>
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
      <dt>Your Role</dt>
      <dd><?= ucfirst($raterRole) ?></dd>
    </div>
  </dl>

  <?php if (!$hasRatedUser): ?>
    <form method="post" action="/rate/user" aria-labelledby="user-rating-heading">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="borrow_id" value="<?= $borrowId ?>">
      <input type="hidden" name="target_id" value="<?= $targetId ?>">
      <input type="hidden" name="role" value="<?= htmlspecialchars($raterRole) ?>">
      <fieldset>
        <legend id="user-rating-heading">Rate <?= htmlspecialchars($targetName) ?></legend>

        <?php if (!empty($_SESSION['rating_errors']['user_score'])): ?>
          <p role="alert" data-field-error><?= htmlspecialchars($_SESSION['rating_errors']['user_score']) ?></p>
        <?php endif; ?>

        <div role="radiogroup" aria-label="User score" data-stars>
          <?php $selectedUser = (int) ($old['user_score'] ?? 0); ?>
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input
              type="radio"
              id="user-score-<?= $i ?>"
              name="user_score"
              value="<?= $i ?>"
              required
              <?= $selectedUser === $i ? 'checked' : '' ?>
            >
            <label for="user-score-<?= $i ?>" title="<?= $scoreLabels[$i] ?>">
              <i class="fa-solid fa-star" aria-hidden="true"></i>
              <span class="visually-hidden"><?= $i ?> star<?= $i > 1 ? 's' : '' ?> — <?= $scoreLabels[$i] ?></span>
            </label>
          <?php endfor; ?>
        </div>

        <?php if (!empty($_SESSION['rating_errors']['user_review'])): ?>
          <p role="alert" data-field-error><?= htmlspecialchars($_SESSION['rating_errors']['user_review']) ?></p>
        <?php endif; ?>

        <label for="user-review">Review (optional)</label>
        <textarea
          id="user-review"
          name="user_review"
          rows="3"
          maxlength="2000"
          placeholder="Share your experience with this <?= $raterRole === 'borrower' ? 'lender' : 'borrower' ?>…"
        ><?= htmlspecialchars($old['user_review'] ?? '') ?></textarea>

        <button type="submit">
          <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
          Submit User Rating
        </button>
      </fieldset>
    </form>
    <?php unset($_SESSION['rating_errors']); ?>
  <?php else: ?>
    <section aria-labelledby="user-rated-heading" data-completed>
      <h2 id="user-rated-heading">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        User Rating Submitted
      </h2>
      <p>You've already rated <?= htmlspecialchars($targetName) ?>.</p>
    </section>
  <?php endif; ?>

  <?php if ($isBorrower && !$hasRatedTool): ?>
    <form method="post" action="/rate/tool" aria-labelledby="tool-rating-heading">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="borrow_id" value="<?= $borrowId ?>">
      <fieldset>
        <legend id="tool-rating-heading">Rate <?= $toolName ?></legend>

        <?php if (!empty($_SESSION['rating_errors']['tool_score'])): ?>
          <p role="alert" data-field-error><?= htmlspecialchars($_SESSION['rating_errors']['tool_score']) ?></p>
        <?php endif; ?>

        <div role="radiogroup" aria-label="Tool score" data-stars>
          <?php $selectedTool = (int) ($old['tool_score'] ?? 0); ?>
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input
              type="radio"
              id="tool-score-<?= $i ?>"
              name="tool_score"
              value="<?= $i ?>"
              required
              <?= $selectedTool === $i ? 'checked' : '' ?>
            >
            <label for="tool-score-<?= $i ?>" title="<?= $scoreLabels[$i] ?>">
              <i class="fa-solid fa-star" aria-hidden="true"></i>
              <span class="visually-hidden"><?= $i ?> star<?= $i > 1 ? 's' : '' ?> — <?= $scoreLabels[$i] ?></span>
            </label>
          <?php endfor; ?>
        </div>

        <?php if (!empty($_SESSION['rating_errors']['tool_review'])): ?>
          <p role="alert" data-field-error><?= htmlspecialchars($_SESSION['rating_errors']['tool_review']) ?></p>
        <?php endif; ?>

        <label for="tool-review">Review (optional)</label>
        <textarea
          id="tool-review"
          name="tool_review"
          rows="3"
          maxlength="2000"
          placeholder="How was the tool's condition and performance?"
        ><?= htmlspecialchars($old['tool_review'] ?? '') ?></textarea>

        <button type="submit">
          <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
          Submit Tool Rating
        </button>
      </fieldset>
    </form>
  <?php elseif ($isBorrower && $hasRatedTool): ?>
    <section aria-labelledby="tool-rated-heading" data-completed>
      <h2 id="tool-rated-heading">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        Tool Rating Submitted
      </h2>
      <p>You've already rated <?= $toolName ?>.</p>
    </section>
  <?php endif; ?>

  <nav aria-label="Navigation">
    <a href="/dashboard">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Back to Dashboard
    </a>
  </nav>

</section>
