<?php
$isNearbyFallback ??= false;
$selectedCity     ??= 'Asheville';
$nearbyMembers    ??= [];
$featuredTools    ??= [];
$friendlyNeighbors ??= [];
$bookmarkedIds     ??= [];
$bookmarkFlash    ??= '';
$platformStats    ??= ['totalMembers' => 0, 'activeMembers' => 0, 'availableTools' => 0, 'completedBorrows' => 0];
?>

<div class="home-page">
  <header>
    <section aria-label="Hero section">
      <?php require BASE_PATH . '/src/Views/partials/nav.php'; ?>
      <div>
        <div>
          <h1 id="hero-heading">Share Tools, Build Community</h1>
          <p>Borrow tools from your neighbors. Lend yours when you're not using them.</p>
          <?php if ($platformStats['availableTools'] > 0 || $platformStats['activeMembers'] > 0 || $platformStats['completedBorrows'] > 0): ?>
            <ul aria-label="Platform highlights">
              <li>
                <i class="fa-solid fa-toolbox" aria-hidden="true"></i>
                <strong data-target="<?= htmlspecialchars((string) $platformStats['availableTools']) ?>"><?= htmlspecialchars(number_format($platformStats['availableTools'])) ?></strong>
                <span>Tools Available</span>
              </li>
              <li>
                <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                <strong data-target="<?= htmlspecialchars((string) $platformStats['activeMembers']) ?>"><?= htmlspecialchars(number_format($platformStats['activeMembers'])) ?></strong>
                <span>Active Members</span>
              </li>
              <li>
                <i class="fa-solid fa-handshake" aria-hidden="true"></i>
                <strong data-target="<?= htmlspecialchars((string) $platformStats['completedBorrows']) ?>"><?= htmlspecialchars(number_format($platformStats['completedBorrows'])) ?></strong>
                <span>Borrows This Month</span>
              </li>
            </ul>
          <?php endif; ?>
        </div>

        <div>
          <form role="search" aria-label="Search tools" action="/tools" method="get">
            <label for="search-tools" class="visually-hidden">Search tools</label>
            <span aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="search" id="search-tools" name="q" placeholder="Search tools near you ..." autocomplete="off" data-suggest="tools">
            <button type="submit" data-intent="primary" data-shape="pill"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Search</button>
          </form>
          <div>
            <a href="/tools" role="button" data-intent="primary" data-size="lg"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Browse Tools</a>
            <?php if ($isLoggedIn): ?>
              <a href="/tools/create" role="button" data-intent="secondary" data-size="lg"><i class="fa-solid fa-plus" aria-hidden="true"></i> List a Tool</a>
            <?php else: ?>
              <a href="/register" role="button" data-intent="success" data-size="lg"><i class="fa-solid fa-mountain" aria-hidden="true"></i> Join Now</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </header>

  <main id="main-content">
    <?php if (!empty($bookmarkFlash)): ?>
      <p role="status" data-flash="success"><?= htmlspecialchars($bookmarkFlash) ?></p>
    <?php endif; ?>

    <section aria-labelledby="popular-heading">
      <h2 id="popular-heading"><i class="fa-solid fa-fire" aria-hidden="true"></i> Popular Picks</h2>
      <?php if (!empty($featuredTools)): ?>
        <div role="list">
          <?php $cardHeadingLevel = 'h3'; $cardSizes = '220px'; ?>
          <?php foreach ($featuredTools as $toolIndex => $tool): ?>
            <?php $eagerLoad = ($toolIndex === 0); ?>
            <?php require BASE_PATH . '/src/Views/partials/tool-card.php'; ?>
          <?php endforeach; ?>
          <?php unset($cardHeadingLevel, $cardSizes); ?>
        </div>
      <?php else: ?>
        <p>No tools available yet.
          <a href="<?= $isLoggedIn ? '/tools/create' : '/register' ?>">Be the first to list one!</a>
        </p>
      <?php endif; ?>
    </section>

    <section aria-labelledby="members-heading">
      <?php if ($isNearbyFallback): ?>
        <h2 id="members-heading"><i class="fa-solid fa-people-group" aria-hidden="true"></i> Top Members</h2>
      <?php else: ?>
        <h2 id="members-heading"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Members Near You</h2>
      <?php endif; ?>

      <nav aria-label="Filter by city" id="location-toggle" hidden>
        <a href="/?location=Asheville" data-city="Asheville"
           <?= strcasecmp($selectedCity, 'Asheville') === 0 ? 'aria-current="true"' : '' ?>>
          <i class="fa-solid fa-mountain" aria-hidden="true"></i> Asheville
        </a>
        <a href="/?location=Hendersonville" data-city="Hendersonville"
           <?= strcasecmp($selectedCity, 'Hendersonville') === 0 ? 'aria-current="true"' : '' ?>>
          <i class="fa-solid fa-tree" aria-hidden="true"></i> Hendersonville
        </a>
      </nav>

      <div id="member-carousel">
        <button type="button" aria-label="Previous members" hidden data-dir="prev">
          <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <div id="member-list">
          <?php if (!empty($nearbyMembers)): ?>
            <?php foreach ($nearbyMembers as $member): ?>
              <?php $displayName = $member['username'] ?? $member['name'] ?? 'Member'; ?>
              <?php
                if (!empty($member['vector_avatar'])) {
                    $memberAvatarSrc = '/uploads/vectors/' . $member['vector_avatar'];
                } elseif (!empty($member['avatar'])) {
                    $memberAvatarSrc = '/uploads/profiles/' . $member['avatar'];
                } else {
                    $memberAvatarSrc = '/assets/images/avatar-placeholder.svg';
                }
              ?>
              <a href="/profile/<?= (int) $member['id_acc'] ?>">
                <img src="<?= htmlspecialchars($memberAvatarSrc) ?>"
                     alt="<?= htmlspecialchars($displayName) ?>"
                     width="80" height="80"
                     loading="lazy"
                     decoding="async">
                <?php if (!empty($member['is_top_member'])): ?>
                  <span role="img" aria-label="Top member"><i class="fa-solid fa-award" aria-hidden="true"></i></span>
                <?php endif; ?>
                <h3><?= htmlspecialchars($displayName) ?></h3>
                <?php if ((int) ($member['total_rating_count'] ?? 0) > 0): ?>
                  <p>
                    <?php
                      $rating = round(($member['avg_rating'] ?? 0) * 2) / 2;
                      $fullStars = (int) floor($rating);
                      $halfStar = ($rating - $fullStars) >= 0.5;
                      $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                    ?>
                    <?php for ($i = 0; $i < $fullStars; $i++): ?>
                      <i class="fa-solid fa-star" aria-hidden="true"></i>
                    <?php endfor; ?>
                    <?php if ($halfStar): ?>
                      <i class="fa-solid fa-star-half-stroke" aria-hidden="true"></i>
                    <?php endif; ?>
                    <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                      <i class="fa-regular fa-star" aria-hidden="true"></i>
                    <?php endfor; ?>
                    <span class="visually-hidden"><?= htmlspecialchars(number_format($member['avg_rating'] ?? 0, 1)) ?> out of 5 stars</span>
                  </p>
                <?php else: ?>
                  <p>No ratings yet</p>
                <?php endif; ?>
                <p>
                  <i class="fa-solid fa-map-pin" aria-hidden="true"></i>
                  <?= htmlspecialchars($member['neighborhood'] ?? $selectedCity) ?>
                </p>
                <span aria-hidden="true">View Profile</span>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No members found in this area yet.</p>
          <?php endif; ?>
        </div>
        <button type="button" aria-label="Next members" hidden data-dir="next">
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
      </div>
    </section>

    <section aria-labelledby="neighbors-heading">
      <h2 id="neighbors-heading"><i class="fa-solid fa-people-group" aria-hidden="true"></i> Friendly Neighbors</h2>
      <div>
        <?php if (!empty($friendlyNeighbors)): ?>
          <?php foreach ($friendlyNeighbors as $neighbor): ?>
            <a href="/profile/<?= (int) $neighbor['id_acc'] ?>">
              <?php if (!empty($neighbor['is_top_member'])): ?>
                <span role="img" aria-label="Top member"><i class="fa-solid fa-award" aria-hidden="true"></i></span>
              <?php endif; ?>
              <?php
                if (!empty($neighbor['vector_avatar'])) {
                    $neighborAvatarSrc = '/uploads/vectors/' . $neighbor['vector_avatar'];
                } elseif (!empty($neighbor['avatar'])) {
                    $neighborAvatarSrc = '/uploads/profiles/' . $neighbor['avatar'];
                } else {
                    $neighborAvatarSrc = '/assets/images/avatar-placeholder.svg';
                }
              ?>
              <img src="<?= htmlspecialchars($neighborAvatarSrc) ?>"
                   alt="<?= htmlspecialchars($neighbor['username']) ?>"
                   width="80" height="80"
                   loading="lazy" decoding="async">
              <h3><?= htmlspecialchars($neighbor['username']) ?></h3>
              <?php if ((int) ($neighbor['total_rating_count'] ?? 0) > 0): ?>
                <p>
                  <?php
                    $rating = round(($neighbor['avg_rating'] ?? 0) * 2) / 2;
                    $fullStars = (int) floor($rating);
                    $halfStar = ($rating - $fullStars) >= 0.5;
                    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                  ?>
                  <?php for ($i = 0; $i < $fullStars; $i++): ?>
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                  <?php endfor; ?>
                  <?php if ($halfStar): ?>
                    <i class="fa-solid fa-star-half-stroke" aria-hidden="true"></i>
                  <?php endif; ?>
                  <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                    <i class="fa-regular fa-star" aria-hidden="true"></i>
                  <?php endfor; ?>
                  <span class="visually-hidden"><?= htmlspecialchars(number_format($neighbor['avg_rating'] ?? 0, 1)) ?> out of 5 stars</span>
                </p>
              <?php else: ?>
                <p>No ratings yet</p>
              <?php endif; ?>
              <p>
                <?php
                  $toolCount = (int) ($neighbor['tools_owned'] ?? 0);
                  $borrowCount = (int) ($neighbor['completed_borrows'] ?? 0);
                ?>
                <i class="fa-solid fa-toolbox" aria-hidden="true"></i>
                <?= $toolCount ?> tool<?= $toolCount !== 1 ? 's' : '' ?> shared
                <span aria-hidden="true">&middot;</span>
                <i class="fa-solid fa-handshake" aria-hidden="true"></i>
                <?= $borrowCount ?> borrow<?= $borrowCount !== 1 ? 's' : '' ?>
              </p>
              <?php if (trim($neighbor['bio'] ?? '') !== ''): ?>
                <blockquote><?= htmlspecialchars($neighbor['bio']) ?></blockquote>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>
            <?php if ($isLoggedIn): ?>
              <i class="fa-solid fa-seedling" aria-hidden="true"></i> Be the first — <a href="/tools/create">list a tool</a> and introduce yourself to the neighborhood.
            <?php else: ?>
              <i class="fa-solid fa-seedling" aria-hidden="true"></i> Your neighbors are waiting — <a href="/register">join the community</a> to get started.
            <?php endif; ?>
          </p>
        <?php endif; ?>
      </div>
    </section>

    <section id="home-cta" aria-labelledby="cta-heading">
      <h2 id="cta-heading" class="visually-hidden">Get Started</h2>
      <div>
        <a href="/tools">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <h3>Find What You Need</h3>
          <p>Browse tools shared by neighbors in your area. Why buy when you can borrow?</p>
          <span aria-hidden="true">Browse Tools <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
        <?php if ($isLoggedIn): ?>
          <a href="/tools/create">
            <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>
            <h3>Share What You Have</h3>
            <p>List your tools and help a neighbor out. Earn trust, build community, and keep things out of landfills.</p>
            <span aria-hidden="true">List a Tool <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
          </a>
        <?php else: ?>
          <a href="/register">
            <i class="fa-solid fa-people-group" aria-hidden="true"></i>
            <h3>Join the Community</h3>
            <p>Create a free account to borrow and lend tools with your neighbors. It takes less than a minute.</p>
            <span aria-hidden="true">Sign Up Free <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
          </a>
        <?php endif; ?>
      </div>
    </section>
  </main>
</div>
