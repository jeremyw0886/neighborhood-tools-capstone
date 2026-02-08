<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'NeighborhoodTools') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description ?? 'Your neighborhood tool sharing platform') ?>">
    <link rel="preload" href="/assets/css/base.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" as="style" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="/assets/css/base.css">
    <?php if (!empty($css)): ?>
      <?php foreach ((array) $css as $sheet): ?>
    <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($sheet) ?>.css">
      <?php endforeach; ?>
    <?php endif; ?>
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
