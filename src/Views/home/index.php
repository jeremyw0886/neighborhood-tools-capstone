<div class="home-page">
  <header>
    <section aria-label="Hero section">
      <div>
        <div>
          <a href="/" aria-label="NeighborhoodTools home">
            <?php include BASE_PATH . '/public/assets/images/logo.svg'; ?>
          </a>
        </div>
        <nav aria-label="Main navigation">
          <button id="mobile-menu-toggle" type="button" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="top-links">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
          </button>
          <ul id="top-links" role="list">
            <li><a href="#"><i class="fa-solid fa-book" aria-hidden="true"></i> How To</a></li>
            <li><a href="/tos"><i class="fa-solid fa-file-contract" aria-hidden="true"></i> Terms of Service</a></li>
            <li><a href="#"><i class="fa-solid fa-circle-question" aria-hidden="true"></i> FAQ's</a></li>
            <li><a href="/tools"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Browse Tools</a></li>
          </ul>
          <div>
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
              <a href="/dashboard" role="button">
                <i class="fa-solid fa-gauge" aria-hidden="true"></i> <?= htmlspecialchars($_SESSION['user_first_name'] ?? 'My') ?>'s Dashboard
              </a>
              <button id="hero-dropdown-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-label="More options">
                <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
              </button>
              <ul id="hero-dropdown-menu" role="menu">
                <li role="menuitem"><a href="/notifications" aria-label="Notifications"><i class="fa-solid fa-bell" aria-hidden="true"></i></a></li>
                <li role="menuitem">
                  <form action="/logout" method="post">
                    <button type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Logout</button>
                  </form>
                </li>
              </ul>
            <?php else: ?>
              <a href="/login" role="button">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Log in
              </a>
              <button id="hero-dropdown-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-label="More options">
                <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
              </button>
              <ul id="hero-dropdown-menu" role="menu">
                <li role="menuitem"><a href="/register"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Sign Up</a></li>
                <li role="menuitem"><a href="/tools"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Browse Tools</a></li>
              </ul>
            <?php endif; ?>
          </div>
        </nav>
      </div>
      <section aria-labelledby="hero-heading">
        <h1 id="hero-heading">Share Tools, Build Community</h1>
        <p>Borrow tools from your neighbors. Lend yours when you're not using them.</p>
        <div>
          <a href="/tools" role="button"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Browse Tools</a>
          <a href="/register" role="button"><i class="fa-solid fa-mountain" aria-hidden="true"></i> Join Now</a>
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
      <h3 id="sidebar-heading"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Members Near You</h3>
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
              <h4><?= htmlspecialchars($member['name']) ?></h4>
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
