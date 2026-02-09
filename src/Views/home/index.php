<div class="home-page">
  <header>
    <section aria-label="Hero section">
      <?php require BASE_PATH . '/src/Views/partials/nav.php'; ?>
      <section aria-labelledby="hero-heading">
        <h1 id="hero-heading">Share Tools, Build Community</h1>
        <p>Borrow tools from your neighbors. Lend yours when you're not using them.</p>
        <div>
          <a href="/tools" role="button"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Browse Tools</a>
          <?php if ($isLoggedIn): ?>
            <a href="/tools/create" role="button"><i class="fa-solid fa-plus" aria-hidden="true"></i> List a Tool</a>
          <?php else: ?>
            <a href="/register" role="button"><i class="fa-solid fa-mountain" aria-hidden="true"></i> Join Now</a>
          <?php endif; ?>
        </div>
        <form role="search" aria-label="Search tools" action="/tools" method="get">
          <label for="search-tools" class="visually-hidden">Search tools</label>
          <span aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input type="search" id="search-tools" name="q" placeholder="Search tools near the mountains...">
          <button type="submit"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Search</button>
        </form>
      </section>
    </section>
    <aside aria-labelledby="sidebar-heading">
      <h2 id="sidebar-heading"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Members Near You</h2>
      <fieldset aria-label="Select your area">
        <legend class="visually-hidden">Choose location</legend>
        <input type="radio" id="loc-asheville" name="location" value="asheville" checked>
        <label for="loc-asheville"><i class="fa-solid fa-mountain" aria-hidden="true"></i> Asheville</label>
        <input type="radio" id="loc-hendersonville" name="location" value="hendersonville">
        <label for="loc-hendersonville"><i class="fa-solid fa-tree" aria-hidden="true"></i> Hendersonville</label>
      </fieldset>
      <section aria-label="Members list">
        <?php if (!empty($nearbyMembers)): ?>
          <?php foreach (array_slice($nearbyMembers, 0, 3) as $member): ?>
            <article aria-label="<?= htmlspecialchars($member['name']) ?> member card">
              <a href="/profile/<?= (int) $member['id_acc'] ?>">
                <img src="<?= htmlspecialchars($member['avatar'] ? '/uploads/profiles/' . $member['avatar'] : '/assets/images/avatar-placeholder.png') ?>"
                     alt="<?= htmlspecialchars($member['name']) ?>"
                     width="60" height="60"
                     loading="lazy"
                     decoding="async">
              </a>
              <h3><?= htmlspecialchars($member['name']) ?></h3>
              <p>
                <?php $avg = round($member['avg_rating'] ?? 0); ?>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="fa-<?= $i <= $avg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
                <?php endfor; ?>
                <span class="visually-hidden"><?= $avg ?> out of 5 stars</span>
              </p>
              <p>
                <i class="fa-solid fa-map-pin" aria-hidden="true"></i> <?= htmlspecialchars($member['neighborhood'] ?? 'Unknown') ?>
              </p>
              <a href="/profile/<?= (int) $member['id_acc'] ?>" role="button"><i class="fa-solid fa-mountain-sun" aria-hidden="true"></i> View Profile</a>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No members found in this area yet.</p>
        <?php endif; ?>
      </section>
    </aside>
  </header>

  <main id="main-content">
    <section aria-labelledby="popular-heading">
      <h2 id="popular-heading"><i class="fa-solid fa-fire" aria-hidden="true"></i> Popular Picks</h2>
      <div role="list">
        <?php if (!empty($featuredTools)): ?>
          <?php foreach ($featuredTools as $tool): ?>
            <?php require BASE_PATH . '/src/Views/partials/tool-card.php'; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No tools available yet. Be the first to list one!</p>
        <?php endif; ?>
      </div>
    </section>

    <section aria-labelledby="neighbors-heading">
      <h2 id="neighbors-heading"><i class="fa-solid fa-people-group" aria-hidden="true"></i> Friendly Neighbors</h2>
      <div>
        <?php if (!empty($topMembers)): ?>
          <?php foreach (array_slice($topMembers, 0, 4) as $neighbor): ?>
            <a href="/profile/<?= (int) $neighbor['id_acc'] ?>" class="neighbor-card">
              <img src="<?= htmlspecialchars($neighbor['avatar'] ? '/uploads/profiles/' . $neighbor['avatar'] : '/assets/images/avatar-placeholder.png') ?>"
                   alt="<?= htmlspecialchars($neighbor['name']) ?>"
                   width="80" height="80"
                   loading="lazy">
              <h3><?= htmlspecialchars($neighbor['name']) ?></h3>
              <p>
                <?php $avg = round($neighbor['avg_rating'] ?? 0); ?>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="fa-<?= $i <= $avg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
                <?php endfor; ?>
                <span class="visually-hidden"><?= $avg ?> out of 5 stars</span>
              </p>
              <?php if (!empty($neighbor['bio'])): ?>
                <blockquote><?= htmlspecialchars($neighbor['bio']) ?></blockquote>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>Friendly neighbors coming soon!</p>
        <?php endif; ?>
      </div>
    </section>
  </main>
</div>
