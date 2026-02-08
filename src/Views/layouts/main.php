<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'NeighborhoodTools') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description ?? 'Your neighborhood tool sharing platform') ?>">
    <link rel="preload" href="/assets/vendor/fontawesome/css/all.min.css" as="style">
    <link rel="preload" href="/assets/css/style.min.css" as="style">
    <link rel="stylesheet" href="/assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.min.css">
</head>
<body>

  <a href="#main-content" class="skip-link">Skip to main content</a>

  <?php if (empty($heroPage)): ?>
  <header>
    <nav>
      <div>
        <a href="/">
          <?php include BASE_PATH . '/public/assets/images/logo.svg'; ?>
          NeighborhoodTools
        </a>
      </div>
      <ul role="list">
        <li><a href="/">Home</a></li>
        <li><a href="/tools">Browse Tools</a></li>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
          <li><a href="/dashboard">Dashboard</a></li>
          <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin'])): ?>
            <li><a href="/admin">Admin</a></li>
          <?php endif; ?>
          <li>
            <span>Hello, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
            <form action="/logout" method="post">
              <button type="submit">Logout</button>
            </form>
          </li>
        <?php else: ?>
          <li><a href="/login">Login</a></li>
          <li><a href="/register">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main id="main-content">
  <?php endif; ?>

    <?= $content ?>

  <?php if (empty($heroPage)): ?>
  </main>
  <?php endif; ?>

  <footer>
    <p>&copy; <?= date('Y') ?> NeighborhoodTools. Share tools, build community.</p>
  </footer>

</body>
</html>
