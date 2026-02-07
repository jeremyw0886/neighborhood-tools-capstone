<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'NeighborhoodTools') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description ?? 'Your neighborhood tool sharing platform') ?>">
</head>
<body>

  <header>
    <nav>
      <div>
        <a href="/">
          <img src="/assets/images/logo.png" alt="NeighborhoodTools Logo" class="nav-logo">
          NeighborhoodTools
        </a>
      </div>
      <ul>
        <li><a href="/">Home</a></li>
        <li><a href="/tools">Browse Tools</a></li>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
          <li><a href="/dashboard">Dashboard</a></li>
          <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin'])): ?>
            <li><a href="/admin/dashboard">Admin</a></li>
          <?php endif; ?>
          <li>
            <span>Hello, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
            <a href="/logout" class="btn btn-sm">Logout</a>
          </li>
        <?php else: ?>
          <li><a href="/login">Login</a></li>
          <li><a href="/register" class="btn btn-primary">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main>
    <?= $content ?>
  </main>

  <footer>
    <p>&copy; <?= date('Y') ?> NeighborhoodTools. Share tools, build community.</p>
  </footer>

</body>
</html>
