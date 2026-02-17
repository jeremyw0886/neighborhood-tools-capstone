<?php
/**
 * Tool Detail page — displays full info for a single tool.
 *
 * Variables from ToolController::show():
 *   $tool          array  Full row from tool_detail_v + owner_avatar
 *   $isBookmarked  bool   Whether the logged-in user has bookmarked this tool
 *   $isOwner       bool   Whether the logged-in user owns this tool
 *   $borrowErrors  array  Field-keyed errors from a failed borrow request
 *   $borrowOld     array  Sticky values (loan_duration, notes) after failure
 */
?>

<section aria-labelledby="tool-detail-heading">

  <nav aria-label="Back">
    <?php if ($isOwner): ?>
      <a href="/dashboard">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Dashboard
      </a>
    <?php else: ?>
      <a href="<?= htmlspecialchars($backUrl) ?>">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
      </a>
    <?php endif; ?>
  </nav>

  <?php if (!empty($_SESSION['bookmark_flash'])): ?>
    <p role="status"><?= htmlspecialchars($_SESSION['bookmark_flash']) ?></p>
    <?php unset($_SESSION['bookmark_flash']); ?>
  <?php endif; ?>

  <article>
    <header>
      <figure>
        <?php if (!empty($tool['primary_image'])): ?>
          <img src="/uploads/tools/<?= htmlspecialchars($tool['primary_image']) ?>"
               alt="<?= htmlspecialchars($tool['tool_name_tol']) ?>"
               width="600" height="400"
               decoding="async">
        <?php else: ?>
          <img src="/assets/images/tool-placeholder.svg"
               alt="<?= htmlspecialchars($tool['tool_name_tol']) ?>"
               width="600" height="400"
               decoding="async">
        <?php endif; ?>
      </figure>

      <div>
        <h1 id="tool-detail-heading"><?= htmlspecialchars($tool['tool_name_tol']) ?></h1>

        <p>
          <?php $avg = round((float) ($tool['avg_rating'] ?? 0)); ?>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fa-<?= $i <= $avg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
          <?php endfor; ?>
          <span class="visually-hidden"><?= htmlspecialchars((string) $avg) ?> out of 5 stars</span>
          <span>(<?= htmlspecialchars((string) (int) ($tool['rating_count'] ?? 0)) ?> review<?= ((int) ($tool['rating_count'] ?? 0)) !== 1 ? 's' : '' ?>)</span>
        </p>

        <?php $status = $tool['availability_status'] ?? 'UNKNOWN'; ?>
        <dl>
          <dt><i class="fa-solid fa-tag" aria-hidden="true"></i> Condition</dt>
          <dd><?= htmlspecialchars($tool['tool_condition'] ?? 'Unknown') ?></dd>

          <dt><i class="fa-solid fa-dollar-sign" aria-hidden="true"></i> Rental Fee</dt>
          <dd>$<?= htmlspecialchars(number_format((float) ($tool['rental_fee_tol'] ?? 0), 2)) ?>/day</dd>

          <?php if (!empty($tool['is_deposit_required_tol'])): ?>
            <dt><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Deposit</dt>
            <dd>$<?= htmlspecialchars(number_format((float) ($tool['default_deposit_amount_tol'] ?? 0), 2)) ?></dd>
          <?php endif; ?>

          <dt><i class="fa-solid fa-clock" aria-hidden="true"></i> Loan Duration</dt>
          <dd><?= htmlspecialchars((string) (int) ($tool['default_loan_duration_hours_tol'] ?? 24)) ?> hours</dd>

          <?php if (!empty($tool['categories'])): ?>
            <dt><i class="fa-solid fa-tags" aria-hidden="true"></i> Categories</dt>
            <dd><?= htmlspecialchars($tool['categories']) ?></dd>
          <?php endif; ?>

          <dt><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Availability</dt>
          <dd data-availability="<?= htmlspecialchars(strtolower($status)) ?>"><?= htmlspecialchars($status) ?></dd>
        </dl>

        <?php if (!empty($isLoggedIn) && ($authUser['id'] ?? 0) !== (int) $tool['owner_id']): ?>
          <form method="post" action="/tools/<?= (int) $tool['id_tol'] ?>/bookmark">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <button type="submit"
                    aria-label="<?= $isBookmarked ? 'Remove bookmark for' : 'Bookmark' ?> <?= htmlspecialchars($tool['tool_name_tol']) ?>">
              <i class="fa-<?= $isBookmarked ? 'solid' : 'regular' ?> fa-bookmark" aria-hidden="true"></i>
              <?= $isBookmarked ? 'Bookmarked' : 'Bookmark' ?>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </header>

    <?php if (!empty($tool['tool_description_tol'])): ?>
      <section aria-label="Description">
        <h2>About This Tool</h2>
        <p><?= nl2br(htmlspecialchars($tool['tool_description_tol'])) ?></p>
      </section>
    <?php endif; ?>

    <?php if (!empty($tool['preexisting_conditions_tol'])): ?>
      <section aria-label="Known conditions">
        <h2>Known Conditions</h2>
        <p><?= nl2br(htmlspecialchars($tool['preexisting_conditions_tol'])) ?></p>
      </section>
    <?php endif; ?>

    <?php
      $canBorrow = !empty($isLoggedIn)
          && !$isOwner
          && $status === 'AVAILABLE';
    ?>

    <?php if ($canBorrow): ?>
      <?php $defaultDuration = (int) ($tool['default_loan_duration_hours_tol'] ?? 24); ?>

      <section aria-labelledby="borrow-heading">
        <h2 id="borrow-heading"><i class="fa-solid fa-handshake" aria-hidden="true"></i> Request to Borrow</h2>

        <?php if (!empty($borrowErrors['general'])): ?>
          <p role="alert"><?= htmlspecialchars($borrowErrors['general']) ?></p>
        <?php endif; ?>

        <form method="post" action="/borrow/request">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <input type="hidden" name="tool_id" value="<?= (int) $tool['id_tol'] ?>">

          <fieldset>
            <legend>Borrow Details</legend>

            <div>
              <label for="loan-duration">
                Duration (hours) <span aria-hidden="true">*</span>
              </label>
              <select id="loan-duration"
                      name="loan_duration"
                      required
                      <?= !empty($borrowErrors['loan_duration']) ? 'aria-invalid="true" aria-describedby="duration-error"' : '' ?>>
                <?php
                  $options = [6, 12, 24, 48, 72, 120, 168, 336, 504, 720];
                  $selected = (int) ($borrowOld['loan_duration'] ?? $defaultDuration);

                  if (!in_array($defaultDuration, $options, true)) {
                      $options[] = $defaultDuration;
                      sort($options);
                  }

                  foreach ($options as $hours): ?>
                    <option value="<?= htmlspecialchars((string) $hours) ?>"<?= $hours === $selected ? ' selected' : '' ?>>
                      <?= htmlspecialchars((string) $hours) ?> hours (<?= $hours < 24 ? $hours . 'h' : round($hours / 24, 1) . ' day' . (round($hours / 24, 1) !== 1.0 ? 's' : '') ?>)
                    </option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($borrowErrors['loan_duration'])): ?>
                <p role="alert" id="duration-error"><?= htmlspecialchars($borrowErrors['loan_duration']) ?></p>
              <?php endif; ?>
            </div>

            <div>
              <label for="borrow-notes">Notes for the lender</label>
              <textarea id="borrow-notes"
                        name="notes"
                        rows="3"
                        placeholder="Tell the lender what you need the tool for or when you'd like to pick it up…"><?= htmlspecialchars($borrowOld['notes'] ?? '') ?></textarea>
            </div>
          </fieldset>

          <?php if (!empty($tool['is_deposit_required_tol'])): ?>
            <p>
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              A $<?= htmlspecialchars(number_format((float) ($tool['default_deposit_amount_tol'] ?? 0), 2)) ?> security deposit will be required before pickup.
            </p>
          <?php endif; ?>

          <button type="submit">
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send Request
          </button>
        </form>
      </section>
    <?php elseif (!empty($isLoggedIn) && !$isOwner && $status !== 'AVAILABLE'): ?>
      <section aria-label="Unavailable notice">
        <p>
          <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
          This tool is not available for borrowing right now.
        </p>
      </section>
    <?php elseif (empty($isLoggedIn)): ?>
      <section aria-label="Login prompt">
        <p>
          <a href="/login"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Log in</a> to request this tool.
        </p>
      </section>
    <?php endif; ?>

    <aside aria-label="Owner information">
      <h2>Listed By</h2>
      <a href="/profile/<?= (int) ($tool['owner_id'] ?? 0) ?>">
        <img src="<?= htmlspecialchars(($tool['owner_avatar'] ?? null) ? '/uploads/profiles/' . $tool['owner_avatar'] : '/assets/images/avatar-placeholder.svg') ?>"
             alt="<?= htmlspecialchars($tool['owner_name'] ?? 'Owner') ?>"
             width="48" height="48"
             loading="lazy"
             decoding="async">
        <span><?= htmlspecialchars($tool['owner_name'] ?? 'Unknown') ?></span>
      </a>
      <?php if (!empty($tool['owner_neighborhood'])): ?>
        <p><i class="fa-solid fa-map-pin" aria-hidden="true"></i> <?= htmlspecialchars($tool['owner_neighborhood']) ?></p>
      <?php endif; ?>
    </aside>
  </article>

</section>
