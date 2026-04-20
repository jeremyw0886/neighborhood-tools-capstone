<?php
/**
 * Public profile page — header, bio, ratings, and listed tools.
 *
 * Variables from ProfileController::show():
 *
 * @var array  $profile    Public-safe fields from account_profile_v
 * @var ?array $reputation Detail from user_reputation_v (live, counts, overall avg)
 * @var array  $tools      Tool rows from Tool::getByOwner()
 * @var int    $totalTools Total tools owned (for pagination)
 * @var int    $page       Current page (1-based)
 * @var int    $totalPages Total pages
 * @var int    $perPage    Results per page (12)
 *
 * Shared data:
 *
 * @var bool   $isLoggedIn
 * @var ?array $authUser
 * @var string $csrfToken
 */

$hasPhoto = !empty($profile['primary_image']) && empty($profile['vector_avatar']);
$avatarSrcsets ??= null;

if (!empty($profile['vector_avatar'])) {
    $avatarSrc = '/uploads/vectors/' . $profile['vector_avatar'];
    $avatarAlt = $profile['vector_avatar_alt'] ?? ($profile['username'] . ' profile picture');
} elseif (!empty($profile['primary_image'])) {
    $avatarSrc = '/uploads/profiles/' . $profile['primary_image'];
    $avatarAlt = $profile['image_alt_text'] ?? ($profile['username'] . ' profile picture');
} else {
    $avatarSrc = '/assets/images/avatar-placeholder.svg';
    $avatarAlt = $profile['username'] . ' profile picture';
}

$focalX = (int) ($profile['focal_x'] ?? 50);
$focalY = (int) ($profile['focal_y'] ?? 50);
$focalAttrs = ($hasPhoto && ($focalX !== 50 || $focalY !== 50))
    ? " data-focal-x=\"{$focalX}\" data-focal-y=\"{$focalY}\""
    : '';

$locationStr = \App\Core\ViewHelper::formatLocation(
    $profile['neighborhood'],
    $profile['city'],
    $profile['state'],
);

// Pagination URL helper — profile pages have no filters to preserve
$paginationUrl = static fn(int $pageNum): string =>
    '/profile/' . $profile['id'] . ($pageNum > 1 ? '?page=' . $pageNum : '');

// Pagination range display
$rangeStart = $totalTools > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalTools);
?>

<article aria-labelledby="profile-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <header>
    <h1 id="profile-heading">
      <?php if ($hasPhoto && $avatarSrcsets !== null): ?>
        <?php $isWebp = str_ends_with($profile['primary_image'], '.webp'); ?>
        <picture>
          <?php if (!$isWebp && $avatarSrcsets['avifSrcset'] !== ''): ?>
            <source type="image/avif"
                    srcset="<?= $avatarSrcsets['avifSrcset'] ?>"
                    sizes="150px">
          <?php endif; ?>
          <?php if (!$isWebp && $avatarSrcsets['webpSrcset'] !== ''): ?>
            <source type="image/webp"
                    srcset="<?= $avatarSrcsets['webpSrcset'] ?>"
                    sizes="150px">
          <?php endif; ?>
          <img src="<?= htmlspecialchars(\App\Core\ViewHelper::uploadVersion($avatarSrc)) ?>"
               srcset="<?= $avatarSrcsets['srcset'] ?>"
               sizes="150px"
               alt="<?= htmlspecialchars($avatarAlt) ?>"
               width="150" height="150"
               decoding="async"<?= $focalAttrs ?>>
        </picture>
      <?php else: ?>
        <img src="<?= htmlspecialchars($avatarSrc) ?>"
             alt="<?= htmlspecialchars($avatarAlt) ?>"
             width="150" height="150"
             decoding="async">
      <?php endif; ?>
      <?= htmlspecialchars($profile['username']) ?>
    </h1>

    <p>
      <?php if ($locationStr !== ''): ?>
        <span>
          <i class="fa-solid fa-map-pin" aria-hidden="true"></i>
          <?= htmlspecialchars($locationStr) ?>
        </span>
      <?php endif; ?>
      <span>
        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
        <time datetime="<?= htmlspecialchars($profile['member_since']) ?>">
          Member since <?= date('F Y', strtotime($profile['member_since'])) ?>
        </time>
      </span>
    </p>

    <?php if ($profile['active_tool_count'] > 0 || ($reputation !== null && (int) ($reputation['completed_borrows'] ?? 0) > 0)): ?>
      <ul aria-label="Activity stats">
        <?php if ($profile['active_tool_count'] > 0): ?>
          <li>
            <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
            <?= htmlspecialchars((string) $profile['active_tool_count']) ?> tool<?= $profile['active_tool_count'] !== 1 ? 's' : '' ?> listed
          </li>
        <?php endif; ?>
        <?php if ($reputation !== null && (int) ($reputation['completed_borrows'] ?? 0) > 0): ?>
          <li>
            <i class="fa-solid fa-handshake" aria-hidden="true"></i>
            <?= (int) $reputation['completed_borrows'] ?> completed borrow<?= (int) $reputation['completed_borrows'] !== 1 ? 's' : '' ?>
          </li>
        <?php endif; ?>
      </ul>
    <?php endif; ?>

  </header>

  <div data-dashboard-body>

            <?php if (!empty($profileNotice)): ?>
              <p role="status" data-flash="success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?= htmlspecialchars($profileNotice) ?></p>
            <?php endif; ?>
          
          <section aria-labelledby="ratings-heading">
            <h2 id="ratings-heading"><i class="fa-solid fa-star" aria-hidden="true"></i> Ratings</h2>
            
            <div>
              <div>
        <h3>As a Lender</h3>
        <?php $lenderCount = (int) ($reputation['lender_rating_count'] ?? 0); ?>
        <?php if ($lenderCount > 0): ?>
          <p>
            <?php $lenderAvg = round((float) $profile['lender_rating']); ?>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="fa-<?= $i <= $lenderAvg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
            <?php endfor; ?>
            <span class="visually-hidden"><?= htmlspecialchars((string) $lenderAvg) ?> out of 5 stars</span>
          </p>
          <p><?= number_format((float) $profile['lender_rating'], 1) ?> avg
            (<?= $lenderCount ?> rating<?= $lenderCount !== 1 ? 's' : '' ?>)</p>
        <?php else: ?>
          <p>No ratings yet</p>
        <?php endif; ?>
      </div>

      <div>
        <h3>As a Borrower</h3>
        <?php $borrowerCount = (int) ($reputation['borrower_rating_count'] ?? 0); ?>
        <?php if ($borrowerCount > 0): ?>
          <p>
            <?php $borrowerAvg = round((float) $profile['borrower_rating']); ?>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="fa-<?= $i <= $borrowerAvg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
            <?php endfor; ?>
            <span class="visually-hidden"><?= htmlspecialchars((string) $borrowerAvg) ?> out of 5 stars</span>
          </p>
          <p><?= number_format((float) $profile['borrower_rating'], 1) ?> avg
            (<?= $borrowerCount ?> rating<?= $borrowerCount !== 1 ? 's' : '' ?>)</p>
        <?php else: ?>
          <p>No ratings yet</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if (!empty($profile['bio'])): ?>
    <section aria-labelledby="bio-heading">
      <h2 id="bio-heading"><i class="fa-solid fa-user" aria-hidden="true"></i> About</h2>
      <p><?= nl2br(htmlspecialchars($profile['bio']), false) ?></p>
    </section>
  <?php endif; ?>

  <section aria-labelledby="tools-heading">
    <h2 id="tools-heading">
      <i class="fa-solid fa-toolbox" aria-hidden="true"></i>
      <?= htmlspecialchars($profile['username']) ?>&rsquo;s Tools
    </h2>

    <?php if ($totalTools > 0): ?>

      <div aria-live="polite" aria-atomic="true">
        <?php if ($totalTools > $perPage): ?>
          <p>
            Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>&ndash;<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
            <strong><?= number_format($totalTools) ?></strong>
            tool<?= $totalTools !== 1 ? 's' : '' ?>
          </p>
        <?php endif; ?>
      </div>

      <div role="list">
        <?php $cardHeadingLevel = 'h3'; ?>
        <?php foreach ($tools as $tool): ?>
          <?php require BASE_PATH . '/src/Views/partials/tool-card.php'; ?>
        <?php endforeach; ?>
        <?php unset($cardHeadingLevel); ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav aria-label="Pagination">
          <ul>

            <?php if ($page > 1): ?>
              <li>
                <a href="<?= $paginationUrl($page - 1) ?>"
                   aria-label="Go to previous page">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                  <span>Previous</span>
                </a>
              </li>
            <?php else: ?>
              <li>
                <span aria-disabled="true">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                  <span>Previous</span>
                </span>
              </li>
            <?php endif; ?>

            <?php
            $windowSize = 2;
            $startPage  = max(1, $page - $windowSize);
            $endPage    = min($totalPages, $page + $windowSize);

            if ($startPage > 1): ?>
              <li>
                <a href="<?= $paginationUrl(1) ?>" aria-label="Go to page 1">1</a>
              </li>
              <?php if ($startPage > 2): ?>
                <li><span aria-hidden="true">&hellip;</span></li>
              <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
              <li>
                <?php if ($i === $page): ?>
                  <a href="<?= $paginationUrl($i) ?>"
                     aria-current="page"
                     aria-label="Page <?= htmlspecialchars((string) $i) ?>, current page"><?= htmlspecialchars((string) $i) ?></a>
                <?php else: ?>
                  <a href="<?= $paginationUrl($i) ?>"
                     aria-label="Go to page <?= htmlspecialchars((string) $i) ?>"><?= htmlspecialchars((string) $i) ?></a>
                <?php endif; ?>
              </li>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
              <?php if ($endPage < $totalPages - 1): ?>
                <li><span aria-hidden="true">&hellip;</span></li>
              <?php endif; ?>
              <li>
                <a href="<?= $paginationUrl($totalPages) ?>"
                   aria-label="Go to page <?= htmlspecialchars((string) $totalPages) ?>"><?= htmlspecialchars((string) $totalPages) ?></a>
              </li>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
              <li>
                <a href="<?= $paginationUrl($page + 1) ?>"
                   aria-label="Go to next page">
                  <span>Next</span>
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
              </li>
            <?php else: ?>
              <li>
                <span aria-disabled="true">
                  <span>Next</span>
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </span>
              </li>
            <?php endif; ?>

          </ul>
        </nav>
      <?php endif; ?>

    <?php else: ?>

      <section aria-label="No tools">
        <i class="fa-solid fa-toolbox" aria-hidden="true"></i>
        <h3>No Tools Listed</h3>
        <p><?= htmlspecialchars($profile['username']) ?> hasn&rsquo;t listed any tools yet.</p>
      </section>

    <?php endif; ?>
  </section>

  </div>
</article>
