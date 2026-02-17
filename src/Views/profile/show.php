<?php
/**
 * Public profile page — header, bio, ratings, and listed tools.
 *
 * Variables from ProfileController::show():
 *   $profile       array   Public-safe fields from account_profile_v
 *   $reputation    ?array  Detail from user_reputation_fast_v (counts, overall avg)
 *   $isOwnProfile  bool    Whether the logged-in user is viewing their own profile
 *   $tools         array   Tool rows from Tool::getByOwner()
 *   $totalTools    int     Total tools owned (for pagination)
 *   $page          int     Current page (1-based)
 *   $totalPages    int     Total pages
 *   $perPage       int     Results per page (12)
 *   $stubMessage   ?string Optional stub message (edit page placeholder)
 *
 * Shared data:
 *   $isLoggedIn  bool
 *   $authUser    ?array
 *   $csrfToken   string
 */

// Avatar path with fallback
$avatarSrc = $profile['primary_image']
    ? '/uploads/profiles/' . $profile['primary_image']
    : '/assets/images/avatar-placeholder.svg';

$avatarAlt = $profile['image_alt_text']
    ?? $profile['full_name'];

// Location string — "neighborhood, city, state" (omit missing segments)
$locationParts = array_filter([
    $profile['neighborhood'],
    $profile['city'],
    $profile['state'],
]);
$locationStr = implode(', ', $locationParts);

// Pagination URL helper — profile pages have no filters to preserve
$paginationUrl = static fn(int $pageNum): string =>
    '/profile/' . $profile['id'] . ($pageNum > 1 ? '?page=' . $pageNum : '');

// Pagination range display
$rangeStart = $totalTools > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalTools);
?>

<?php if (!empty($stubMessage)): ?>
  <section aria-label="Notice">
    <p><i class="fa-solid fa-circle-info" aria-hidden="true"></i> <?= htmlspecialchars($stubMessage) ?></p>
  </section>
<?php endif; ?>

<article aria-labelledby="profile-heading">

  <header>
    <img src="<?= htmlspecialchars($avatarSrc) ?>"
         alt="<?= htmlspecialchars($avatarAlt) ?>"
         width="150" height="150"
         decoding="async">

    <div>
      <h1 id="profile-heading"><?= htmlspecialchars($profile['full_name']) ?></h1>

      <?php if ($locationStr !== ''): ?>
        <p>
          <i class="fa-solid fa-map-pin" aria-hidden="true"></i>
          <?= htmlspecialchars($locationStr) ?>
        </p>
      <?php endif; ?>

      <p>
        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
        <time datetime="<?= htmlspecialchars($profile['member_since']) ?>">
          Member since <?= date('F Y', strtotime($profile['member_since'])) ?>
        </time>
      </p>

      <?php if ($profile['active_tool_count'] > 0): ?>
        <p>
          <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
          <?= $profile['active_tool_count'] ?> tool<?= $profile['active_tool_count'] !== 1 ? 's' : '' ?> listed
        </p>
      <?php endif; ?>

      <?php if ($reputation !== null && (int) ($reputation['completed_borrows'] ?? 0) > 0): ?>
        <p>
          <i class="fa-solid fa-handshake" aria-hidden="true"></i>
          <?= (int) $reputation['completed_borrows'] ?> completed borrow<?= (int) $reputation['completed_borrows'] !== 1 ? 's' : '' ?>
        </p>
      <?php endif; ?>
    </div>
  </header>

  <section aria-labelledby="ratings-heading">
    <h2 id="ratings-heading"><i class="fa-solid fa-star" aria-hidden="true"></i> Ratings</h2>

    <div>
      <div>
        <h3>As a Lender</h3>
        <?php if ($profile['lender_rating'] !== null): ?>
          <p>
            <?php $lenderAvg = round((float) $profile['lender_rating']); ?>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="fa-<?= $i <= $lenderAvg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
            <?php endfor; ?>
            <span class="visually-hidden"><?= htmlspecialchars((string) $lenderAvg) ?> out of 5 stars</span>
          </p>
          <?php if ($reputation !== null): ?>
            <p><?= number_format((float) $profile['lender_rating'], 1) ?> avg
              (<?= (int) ($reputation['lender_rating_count'] ?? 0) ?> rating<?= (int) ($reputation['lender_rating_count'] ?? 0) !== 1 ? 's' : '' ?>)</p>
          <?php endif; ?>
        <?php else: ?>
          <p>No ratings yet</p>
        <?php endif; ?>
      </div>

      <div>
        <h3>As a Borrower</h3>
        <?php if ($profile['borrower_rating'] !== null): ?>
          <p>
            <?php $borrowerAvg = round((float) $profile['borrower_rating']); ?>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="fa-<?= $i <= $borrowerAvg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
            <?php endfor; ?>
            <span class="visually-hidden"><?= htmlspecialchars((string) $borrowerAvg) ?> out of 5 stars</span>
          </p>
          <?php if ($reputation !== null): ?>
            <p><?= number_format((float) $profile['borrower_rating'], 1) ?> avg
              (<?= (int) ($reputation['borrower_rating_count'] ?? 0) ?> rating<?= (int) ($reputation['borrower_rating_count'] ?? 0) !== 1 ? 's' : '' ?>)</p>
          <?php endif; ?>
        <?php else: ?>
          <p>No ratings yet</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if (!empty($profile['bio'])): ?>
    <section aria-labelledby="bio-heading">
      <h2 id="bio-heading"><i class="fa-solid fa-user" aria-hidden="true"></i> About</h2>
      <p><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
    </section>
  <?php endif; ?>

  <section aria-labelledby="tools-heading">
    <h2 id="tools-heading">
      <i class="fa-solid fa-toolbox" aria-hidden="true"></i>
      <?= htmlspecialchars($profile['first_name']) ?>&rsquo;s Tools
    </h2>

    <?php if ($totalTools > 0): ?>

      <div aria-live="polite" aria-atomic="true">
        <p>
          Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>&ndash;<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
          <strong><?= number_format($totalTools) ?></strong>
          tool<?= $totalTools !== 1 ? 's' : '' ?>
        </p>
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
                <span aria-disabled="true" aria-label="No previous page">
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
                <span aria-disabled="true" aria-label="No next page">
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
        <?php if ($isOwnProfile): ?>
          <p>You haven&rsquo;t listed any tools yet. Share your tools with the community!</p>
          <a href="/tools/create" role="button">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> List Your First Tool
          </a>
        <?php else: ?>
          <p><?= htmlspecialchars($profile['first_name']) ?> hasn&rsquo;t listed any tools yet.</p>
        <?php endif; ?>
      </section>

    <?php endif; ?>
  </section>

</article>
