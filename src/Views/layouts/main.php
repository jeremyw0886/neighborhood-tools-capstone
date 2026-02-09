<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'NeighborhoodTools') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description ?? 'Your neighborhood tool sharing platform') ?>">
    <link rel="preload" href="/assets/vendor/fontawesome/css/all.min.css" as="style">
    <link rel="preload" href="/assets/css/style.min.css?v=<?= ASSET_VERSION ?>" as="style">
    <link rel="stylesheet" href="/assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.min.css?v=<?= ASSET_VERSION ?>">
    <?php if (!empty($pageCss)): ?>
      <?php foreach ((array) $pageCss as $cssFile): ?>
    <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($cssFile) ?>?v=<?= ASSET_VERSION ?>">
      <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

  <a href="#main-content" class="skip-link">Skip to main content</a>

  <?php if (empty($heroPage)): ?>
  <header>
    <?php require BASE_PATH . '/src/Views/partials/nav.php'; ?>
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

  <?php
    $modalPartials = ['modal-how-to.php', 'modal-faq.php', 'modal-tos.php'];
    foreach ($modalPartials as $modal) {
        $path = BASE_PATH . '/src/Views/partials/' . $modal;
        if (file_exists($path)) {
            require $path;
        }
    }
  ?>

</body>
</html>
