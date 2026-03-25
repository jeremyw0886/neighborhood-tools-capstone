<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= htmlspecialchars($title ?? 'NeighborhoodTools') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description ?? 'Your neighborhood tool sharing platform') ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="data:application/manifest+json,<?= rawurlencode(file_get_contents(BASE_PATH . '/public/site.webmanifest')) ?>">
    <link rel="preload" href="/assets/vendor/fontawesome/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/vendor/fontawesome/webfonts/fa-regular-400.woff2" as="font" type="font/woff2" crossorigin>
    <?php foreach ($cdnJs ?? [] as $cdn): ?>
    <?php $cdnOrigin = parse_url($cdn, PHP_URL_SCHEME) . '://' . parse_url($cdn, PHP_URL_HOST); ?>
    <link rel="preconnect" href="<?= htmlspecialchars($cdnOrigin) ?>">
    <link rel="dns-prefetch" href="<?= htmlspecialchars($cdnOrigin) ?>">
    <?php endforeach; ?>
    <link rel="preload" href="/assets/vendor/fontawesome/css/fontawesome-custom.min.css?v=<?= ASSET_VERSION ?>" as="style">
    <link rel="stylesheet" href="/assets/vendor/fontawesome/css/fontawesome-custom.min.css?v=<?= ASSET_VERSION ?>">
    <?php if (($_ENV['APP_ENV'] ?? 'production') === 'development'): ?>
      <?php foreach (require BASE_PATH . '/config/css.php' as $cssFile): ?>
    <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($cssFile) ?>?v=<?= ASSET_VERSION ?>">
      <?php endforeach; ?>
    <?php else: ?>
    <link rel="preload" href="/assets/css/style.min.css?v=<?= ASSET_VERSION ?>" as="style">
    <link rel="stylesheet" href="/assets/css/style.min.css?v=<?= ASSET_VERSION ?>">
    <?php endif; ?>
    <noscript><link rel="stylesheet" href="/assets/css/noscript.css"></noscript>
    <?php foreach ($pageCss ?? [] as $cssFile): ?>
    <?php $cssHref = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? $cssFile : str_replace('.css', '.min.css', $cssFile); ?>
    <link rel="preload" href="/assets/css/<?= htmlspecialchars($cssHref) ?>?v=<?= ASSET_VERSION ?>" as="style">
    <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($cssHref) ?>?v=<?= ASSET_VERSION ?>">
    <?php endforeach; ?>
</head>
<body id="top">

  <a href="#main-content" class="skip-link">Skip to main content</a>

  <?php if (empty($heroPage)): ?>
  <header>
    <?php require BASE_PATH . '/src/Views/partials/nav.php'; ?>
  </header>

  <main id="main-content">
  <?php endif; ?>

    <?php if (!empty($flashError)): ?>
      <p role="alert" data-flash="error"><?= htmlspecialchars($flashError) ?></p>
    <?php endif; ?>

    <?= $content ?>

  <?php if (empty($heroPage)): ?>
  </main>
  <?php endif; ?>

  <?php require BASE_PATH . '/src/Views/partials/footer.php'; ?>

  <?php
    $modalPartials = ['modal-how-to.php', 'modal-faq.php', 'modal-tos.php'];
    foreach ($modalPartials as $modal) {
        $path = BASE_PATH . '/src/Views/partials/' . $modal;
        if (file_exists($path)) {
            require $path;
        }
    }
  ?>

  <?php $ttJs = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'trusted-types.js' : 'trusted-types.min.js'; ?>
  <script src="/assets/js/<?= $ttJs ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php foreach ($cdnJs ?? [] as $cdnUrl): ?>
  <script src="<?= htmlspecialchars($cdnUrl) ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php endforeach; ?>
  <?php $utilsJs = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'utils.js' : 'utils.min.js'; ?>
  <script src="/assets/js/<?= $utilsJs ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php $navJs = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'nav.js' : 'nav.min.js'; ?>
  <script src="/assets/js/<?= $navJs ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php if (isset($_GET['test'])): ?>
  <?php $utCss = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'usability-test.css' : 'usability-test.min.css'; ?>
  <link rel="stylesheet" href="/assets/css/<?= $utCss ?>">
  <?php $utJs = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'usability-test.js' : 'usability-test.min.js'; ?>
  <script src="/assets/js/<?= $utJs ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php endif; ?>
  <?php foreach ($pageJs ?? [] as $jsFile): ?>
  <?php $jsHref = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? $jsFile : str_replace('.js', '.min.js', $jsFile); ?>
  <script src="/assets/js/<?= htmlspecialchars($jsHref) ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php endforeach; ?>

</body>
</html>
