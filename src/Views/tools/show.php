<?php
$isBookmarked  ??= false;
$isOwner       ??= false;
$borrowErrors  ??= [];
$borrowOld     ??= [];
$bookmarkFlash ??= '';
$images        ??= [];
?>

<section aria-labelledby="tool-detail-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <?php if (!empty($bookmarkFlash)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($bookmarkFlash) ?></p>
  <?php endif; ?>

  <article>
    <header>
      <?php
        $primaryImage = null;
        $extraImages  = [];

        foreach ($images as $img) {
            if (!empty($img['is_primary_tim'])) {
                $primaryImage = $img;
            } else {
                $extraImages[] = $img;
            }
        }

        if ($primaryImage === null && $images !== []) {
            $primaryImage = $images[0];
            $extraImages  = array_slice($images, 1);
        }
      ?>

      <div id="tool-gallery" data-count="<?= count($images) ?>">
        <?php if (!empty($isLoggedIn) && ($authUser['id'] ?? 0) !== (int) $tool['owner_id']): ?>
          <form method="post" action="/tools/<?= (int) $tool['id_tol'] ?>/bookmark">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <button type="submit"
                    data-shape="icon"
                    aria-label="<?= $isBookmarked ? 'Remove bookmark for' : 'Bookmark' ?> <?= htmlspecialchars($tool['tool_name_tol']) ?>">
              <i class="fa-<?= $isBookmarked ? 'solid' : 'regular' ?> fa-bookmark" aria-hidden="true"></i>
            </button>
          </form>
        <?php endif; ?>
        <figure id="gallery-main">
          <?php if ($primaryImage !== null):
            $mainVariants = \App\Core\ImageProcessor::getAvailableVariants(
                $primaryImage['file_name_tim'],
                $primaryImage['width_tim'] ?? null,
            );
            $mainSrcsets = \App\Core\ImageProcessor::buildSrcset($mainVariants);
            $mainIsWebp  = str_ends_with($primaryImage['file_name_tim'], '.webp');
            $mainFallback = htmlspecialchars($primaryImage['file_name_tim']);
            $mainAlt   = htmlspecialchars($primaryImage['alt_text_tim'] ?? $tool['tool_name_tol']);
            $mainFx    = (int) ($primaryImage['focal_x_tim'] ?? 50);
            $mainFy    = (int) ($primaryImage['focal_y_tim'] ?? 50);
            $mainSizes = '(max-width: 768px) 100vw, 600px';
            $mainW     = $primaryImage['width_tim'] ?? 750;
            $mainH     = (int) round($mainW / 1.5);
          ?>
            <a href="/uploads/tools/<?= $mainFallback ?>" data-lightbox-trigger>
              <picture>
                <?php if (!$mainIsWebp && $mainSrcsets['webpSrcset'] !== ''): ?>
                  <source type="image/webp"
                          srcset="<?= $mainSrcsets['webpSrcset'] ?>"
                          sizes="<?= $mainSizes ?>"
                          id="gallery-main-source">
                <?php endif; ?>
                <img src="/uploads/tools/<?= $mainFallback ?>"
                     srcset="<?= $mainSrcsets['srcset'] ?>"
                     sizes="<?= $mainSizes ?>"
                     alt="<?= $mainAlt ?>"
                     width="<?= $mainW ?>" height="<?= $mainH ?>"
                     id="gallery-main-img"
                     fetchpriority="high"
                     decoding="sync"
                     <?= ($mainFx !== 50 || $mainFy !== 50) ? "data-focal-x=\"{$mainFx}\" data-focal-y=\"{$mainFy}\"" : '' ?>>
              </picture>
            </a>
            <?php if (($primaryImage['alt_text_tim'] ?? '') !== ''): ?>
              <figcaption><?= htmlspecialchars($primaryImage['alt_text_tim']) ?></figcaption>
            <?php endif; ?>
          <?php else: ?>
            <img src="/assets/images/tool-placeholder.svg"
                 alt="<?= htmlspecialchars($tool['tool_name_tol']) ?>"
                 width="600" height="400"
                 decoding="async">
          <?php endif; ?>
        </figure>

        <?php if ($extraImages !== []): ?>
          <ul id="gallery-thumbs" aria-label="Additional photos">
            <?php if ($primaryImage !== null):
              $mainThumbSrc = htmlspecialchars($mainVariants[array_key_first($mainVariants)]['file'] ?? $primaryImage['file_name_tim']);
            ?>
              <li>
                <button type="button"
                        aria-current="true"
                        aria-label="<?= htmlspecialchars($primaryImage['alt_text_tim'] ?? 'Primary photo') ?>"
                        data-full="/uploads/tools/<?= $mainFallback ?>"
                        data-srcset="<?= htmlspecialchars($mainSrcsets['srcset']) ?>"
                        <?= (!$mainIsWebp && $mainSrcsets['webpSrcset'] !== '') ? 'data-srcset-webp="' . htmlspecialchars($mainSrcsets['webpSrcset']) . '"' : '' ?>
                        data-alt="<?= $mainAlt ?>"
                        data-focal-x="<?= $mainFx ?>"
                        data-focal-y="<?= $mainFy ?>">
                  <img src="/uploads/tools/<?= $mainThumbSrc ?>"
                       alt=""
                       width="80" height="54"
                       loading="lazy"
                       decoding="async"
                       <?= ($mainFx !== 50 || $mainFy !== 50) ? "data-focal-x=\"{$mainFx}\" data-focal-y=\"{$mainFy}\"" : '' ?>>
                </button>
              </li>
            <?php endif; ?>
            <?php foreach ($extraImages as $extra):
              $exVariants = \App\Core\ImageProcessor::getAvailableVariants(
                  $extra['file_name_tim'],
                  $extra['width_tim'] ?? null,
              );
              $exSrcsets  = \App\Core\ImageProcessor::buildSrcset($exVariants);
              $exIsWebp   = str_ends_with($extra['file_name_tim'], '.webp');
              $exFile     = htmlspecialchars($extra['file_name_tim']);
              $exThumbSrc = htmlspecialchars($exVariants[array_key_first($exVariants)]['file'] ?? $extra['file_name_tim']);
              $exAlt      = htmlspecialchars($extra['alt_text_tim'] ?? $tool['tool_name_tol']);
              $exFx       = (int) ($extra['focal_x_tim'] ?? 50);
              $exFy       = (int) ($extra['focal_y_tim'] ?? 50);
            ?>
              <li>
                <button type="button"
                        aria-label="<?= $exAlt ?>"
                        data-full="/uploads/tools/<?= $exFile ?>"
                        data-srcset="<?= htmlspecialchars($exSrcsets['srcset']) ?>"
                        <?= (!$exIsWebp && $exSrcsets['webpSrcset'] !== '') ? 'data-srcset-webp="' . htmlspecialchars($exSrcsets['webpSrcset']) . '"' : '' ?>
                        data-alt="<?= $exAlt ?>"
                        data-focal-x="<?= $exFx ?>"
                        data-focal-y="<?= $exFy ?>">
                  <img src="/uploads/tools/<?= $exThumbSrc ?>"
                       alt=""
                       width="80" height="54"
                       loading="lazy"
                       decoding="async"
                       <?= ($exFx !== 50 || $exFy !== 50) ? "data-focal-x=\"{$exFx}\" data-focal-y=\"{$exFy}\"" : '' ?>>
                </button>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <?php if ($images !== []): ?>
        <dialog id="gallery-lightbox" aria-label="Image viewer">
          <div>
            <img src="" alt="" id="lightbox-img" decoding="async">
            <?php if (count($images) > 1): ?>
              <button type="button" id="lightbox-prev" aria-label="Previous image">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
              </button>
              <button type="button" id="lightbox-next" aria-label="Next image">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </button>
            <?php endif; ?>
            <button type="button" id="lightbox-close" aria-label="Close image viewer">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
        </dialog>
      <?php endif; ?>

      <div>
        <h1 id="tool-detail-heading"><?= htmlspecialchars($tool['tool_name_tol']) ?></h1>

        <?php $ratingCount = (int) ($tool['rating_count'] ?? 0); ?>
        <?php if ($ratingCount > 0): ?>
          <p>
            <?php $avg = round((float) ($tool['avg_rating'] ?? 0)); ?>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="fa-<?= $i <= $avg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
            <?php endfor; ?>
            <span class="visually-hidden"><?= htmlspecialchars((string) $avg) ?> out of 5 stars</span>
            <span>(<?= htmlspecialchars((string) $ratingCount) ?> review<?= $ratingCount !== 1 ? 's' : '' ?>)</span>
          </p>
        <?php else: ?>
          <p>No ratings yet</p>
        <?php endif; ?>

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
          <dd><?= htmlspecialchars(\App\Core\ViewHelper::formatDuration((int) ($tool['default_loan_duration_hours_tol'] ?? 24))) ?></dd>

          <?php if (!empty($tool['fuel_type'])): ?>
            <dt><i class="fa-solid fa-gas-pump" aria-hidden="true"></i> Fuel Type</dt>
            <dd><?= htmlspecialchars(ucwords($tool['fuel_type'], '-/')) ?></dd>
          <?php endif; ?>

          <?php if (!empty($tool['categories'])): ?>
            <dt><i class="fa-solid fa-tags" aria-hidden="true"></i> Categories</dt>
            <dd><?= htmlspecialchars($tool['categories']) ?></dd>
          <?php endif; ?>

          <dt><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Availability</dt>
          <dd data-availability="<?= htmlspecialchars(strtolower($status)) ?>"><?= htmlspecialchars($status) ?></dd>
        </dl>

      </div>
    </header>

    <?php if (!empty($tool['tool_description_tol'])): ?>
      <section aria-label="Description">
        <h2>About This Tool</h2>
        <p><?= nl2br(htmlspecialchars($tool['tool_description_tol']), false) ?></p>
      </section>
    <?php endif; ?>

    <?php if (!empty($tool['preexisting_conditions_tol'])): ?>
      <section aria-label="Known conditions">
        <h2>Known Conditions</h2>
        <p><?= nl2br(htmlspecialchars($tool['preexisting_conditions_tol']), false) ?></p>
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
                Duration <span aria-hidden="true">*</span>
              </label>
              <select id="loan-duration"
                      name="loan_duration"
                      required
                      <?= !empty($borrowErrors['loan_duration']) ? 'aria-invalid="true" aria-describedby="duration-error"' : '' ?>>
                <?php
                  $options = [24, 48, 72, 120, 168, 336, 504, 720];
                  $selected = (int) ($borrowOld['loan_duration'] ?? $defaultDuration);

                  if (!in_array($defaultDuration, $options, true)) {
                      $options[] = $defaultDuration;
                      sort($options);
                  }

                  foreach ($options as $hours): ?>
                    <option value="<?= htmlspecialchars((string) $hours) ?>"<?= $hours === $selected ? ' selected' : '' ?>>
                      <?= htmlspecialchars(\App\Core\ViewHelper::formatDuration($hours)) ?>
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
                        maxlength="2000"
                        aria-describedby="<?= !empty($borrowErrors['notes']) ? 'notes-error' : '' ?>"
                        placeholder="Tell the lender what you need the tool for or when you'd like to pick it up…"><?= htmlspecialchars($borrowOld['notes'] ?? '') ?></textarea>
              <?php if (!empty($borrowErrors['notes'])): ?>
                <p role="alert" id="notes-error"><?= htmlspecialchars($borrowErrors['notes']) ?></p>
              <?php endif; ?>
            </div>
          </fieldset>

          <?php if (!empty($tool['is_deposit_required_tol'])): ?>
            <p>
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              A $<?= htmlspecialchars(number_format((float) ($tool['default_deposit_amount_tol'] ?? 0), 2)) ?> security deposit will be required before pickup.
            </p>
          <?php endif; ?>

          <button type="submit" data-intent="success">
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send Request
          </button>

          <?php if (!empty($turnstileSiteKey)): ?>
            <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>" data-action="borrow_request" data-appearance="interaction-only" data-theme="light" data-callback="onTurnstileVerify" data-expired-callback="onTurnstileExpire" data-error-callback="onTurnstileError"></div>
          <?php endif; ?>
        </form>
      </section>
    <?php elseif (!empty($isLoggedIn) && !$isOwner && $status !== 'AVAILABLE'): ?>
      <section aria-label="Unavailable notice">
        <p>
          <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
          This tool is not available for borrowing right now.
        </p>
      </section>
    <?php elseif (empty($isLoggedIn) && $status === 'AVAILABLE'): ?>
      <section aria-labelledby="borrow-heading">
        <h2 id="borrow-heading"><i class="fa-solid fa-handshake" aria-hidden="true"></i> Want to Borrow This Tool?</h2>
        <p>Log in or create an account to request this tool from the lender.</p>
        <a href="/login?return=<?= htmlspecialchars(urlencode($_SERVER['REQUEST_URI'])) ?>" role="button" data-intent="primary">
          <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Log In to Borrow
        </a>
        <a href="/register?return=<?= htmlspecialchars(urlencode($_SERVER['REQUEST_URI'])) ?>">
          <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Create an Account
        </a>
      </section>
    <?php endif; ?>

    <aside aria-label="Owner information">
      <h2>Listed By</h2>
      <a href="/profile/<?= (int) ($tool['owner_id'] ?? 0) ?>">
        <?php
          if (!empty($tool['owner_vector_avatar'])) {
              $ownerAvatarSrc = '/uploads/vectors/' . $tool['owner_vector_avatar'];
          } elseif (!empty($tool['owner_avatar'])) {
              $ownerAvatarSrc = '/uploads/profiles/' . $tool['owner_avatar'];
          } else {
              $ownerAvatarSrc = '/assets/images/avatar-placeholder.svg';
          }
        ?>
        <img src="<?= htmlspecialchars($ownerAvatarSrc) ?>"
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
