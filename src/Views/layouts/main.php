<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'NeighborhoodTools') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description ?? 'Your neighborhood tool sharing platform') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLGPg8YJ04IY4YZ0Z5ZxuJEC6EPHnR3JluAnip1UQIzmfh73LoR1bBgNw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="/assets/css/base.css">
</head>
<body>

  <?php if (empty($heroPage)): ?>
  <header>
    <nav>
      <div>
        <a href="/">
          <img src="/assets/images/logo.png" alt="NeighborhoodTools Logo">
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
            <a href="/logout">Logout</a>
          </li>
        <?php else: ?>
          <li><a href="/login">Login</a></li>
          <li><a href="/register">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main>
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
