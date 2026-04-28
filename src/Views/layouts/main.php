<?php
/**
 * Main layout — wraps every rendered view.
 *
 * Receives shared data from BaseController::getSharedData() plus any data
 * passed by the controller's render() call. The inner view's output is
 * captured into $content via output buffering before this template runs.
 *
 * Per-page render data:
 *
 * @var string  $content          Pre-rendered HTML body of the inner view
 * @var ?string $title            Page title (defaults to "NeighborhoodTools")
 * @var ?string $description      Meta description
 * @var ?array  $pageCss          Page-specific stylesheets to load
 * @var ?array  $pageJs           Page-specific scripts to load
 * @var ?array  $cdnJs            Third-party CDN scripts (Turnstile, Stripe)
 * @var ?bool   $heroPage         Hide header/footer for hero pages
 * @var ?string $criticalKey      Critical-CSS key (filename stem in /critical/)
 * @var ?array  $preloadImage     Preload hint for an above-the-fold image
 * @var ?bool   $preloadHeroLogo  Preload the hero logo on desktop
 *
 * Shared data:
 *
 * @var string  $csrfToken
 * @var ?string $flashError
 */

$content         ??= '';
$title           ??= 'NeighborhoodTools';
$description     ??= 'Your neighborhood tool sharing platform';
$pageCss         ??= [];
$pageJs          ??= [];
$cdnJs           ??= [];
$heroPage        ??= false;
$criticalKey     ??= null;
$preloadImage    ??= null;
$preloadHeroLogo ??= false;
$csrfToken       ??= '';
$flashError      ??= null;
?>
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
  <link rel="manifest" href="/site.webmanifest">
  <link rel="preload" href="/assets/vendor/fontawesome/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/assets/vendor/fontawesome/webfonts/fa-regular-400.woff2" as="font" type="font/woff2" crossorigin>
  <?php if (!empty($preloadImage)): ?>
  <link rel="preload" as="image"<?= $preloadImage['type'] !== '' ? ' type="' . htmlspecialchars($preloadImage['type']) . '"' : '' ?> imagesrcset="<?= htmlspecialchars($preloadImage['srcset']) ?>" imagesizes="<?= htmlspecialchars($preloadImage['sizes']) ?>" fetchpriority="high">
  <?php endif; ?>
  <?php if (!empty($preloadHeroLogo)): ?>
  <link rel="preload" as="image" href="/assets/images/logo-mark.svg" fetchpriority="high" media="(min-width: 1025px)">
  <?php endif; ?>
  <?php foreach ($cdnJs ?? [] as $cdn): ?>
    <?php $cdnOrigin = parse_url($cdn, PHP_URL_SCHEME) . '://' . parse_url($cdn, PHP_URL_HOST); ?>
    <link rel="preconnect" href="<?= htmlspecialchars($cdnOrigin) ?>">
    <link rel="dns-prefetch" href="<?= htmlspecialchars($cdnOrigin) ?>">
  <?php endforeach; ?>
  <?php
  $criticalFile = !empty($criticalKey) && preg_match('/^[a-z][a-z0-9-]*$/', $criticalKey)
    ? BASE_PATH . '/public/assets/css/critical/' . $criticalKey . '.min.css'
    : null;
  $asyncCss = $criticalFile !== null && is_file($criticalFile);
  ?>
  <?php if ($asyncCss): ?>
    <link rel="preload" href="/assets/vendor/fontawesome/css/fontawesome-custom.min.css?v=<?= ASSET_VERSION ?>" as="style" data-rel-swap>
    <noscript>
      <link rel="stylesheet" href="/assets/vendor/fontawesome/css/fontawesome-custom.min.css?v=<?= ASSET_VERSION ?>">
    </noscript>
  <?php else: ?>
    <link rel="preload" href="/assets/vendor/fontawesome/css/fontawesome-custom.min.css?v=<?= ASSET_VERSION ?>" as="style">
    <link rel="stylesheet" href="/assets/vendor/fontawesome/css/fontawesome-custom.min.css?v=<?= ASSET_VERSION ?>">
  <?php endif; ?>
  <?php if (($_ENV['APP_ENV'] ?? 'production') === 'development'): ?>
    <?php foreach (require BASE_PATH . '/config/css.php' as $cssFile): ?>
      <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($cssFile) ?>?v=<?= ASSET_VERSION ?>">
    <?php endforeach; ?>
  <?php elseif ($asyncCss): ?>
    <link rel="preload" href="/assets/css/style.min.css?v=<?= ASSET_VERSION ?>" as="style" data-rel-swap>
    <noscript>
      <link rel="stylesheet" href="/assets/css/style.min.css?v=<?= ASSET_VERSION ?>">
    </noscript>
  <?php else: ?>
    <link rel="preload" href="/assets/css/style.min.css?v=<?= ASSET_VERSION ?>" as="style">
    <link rel="stylesheet" href="/assets/css/style.min.css?v=<?= ASSET_VERSION ?>">
  <?php endif; ?>
  <noscript>
    <link rel="stylesheet" href="/assets/css/noscript.css">
  </noscript>
  <?php foreach ($pageCss ?? [] as $cssFile): ?>
    <?php $cssHref = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? $cssFile : str_replace('.css', '.min.css', $cssFile); ?>
    <?php if ($asyncCss): ?>
      <link rel="preload" href="/assets/css/<?= htmlspecialchars($cssHref) ?>?v=<?= ASSET_VERSION ?>" as="style" data-rel-swap>
      <noscript>
        <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($cssHref) ?>?v=<?= ASSET_VERSION ?>">
      </noscript>
    <?php else: ?>
      <link rel="preload" href="/assets/css/<?= htmlspecialchars($cssHref) ?>?v=<?= ASSET_VERSION ?>" as="style">
      <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($cssHref) ?>?v=<?= ASSET_VERSION ?>">
    <?php endif; ?>
  <?php endforeach; ?>
  <style id="nt-dynamic-styles" nonce="<?= CSP_NONCE ?>">
    <?php
    if ($asyncCss) {
      readfile($criticalFile);
    }
    ?>
  </style>
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
  require BASE_PATH . '/src/Views/partials/modal-how-to.php';
  require BASE_PATH . '/src/Views/partials/modal-faq.php';
  require BASE_PATH . '/src/Views/partials/modal-tos.php';
  ?>

  <?php $dpJs = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'purify.js' : 'purify.min.js'; ?>
  <script src="/assets/vendor/dompurify/<?= $dpJs ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php $ttJs = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'trusted-types.js' : 'trusted-types.min.js'; ?>
  <script src="/assets/js/<?= $ttJs ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php foreach ($cdnJs ?? [] as $cdnUrl): ?>
    <script src="<?= htmlspecialchars($cdnUrl) ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php endforeach; ?>
  <?php $utilsJs = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'utils.js' : 'utils.min.js'; ?>
  <script src="/assets/js/<?= $utilsJs ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php if ($asyncCss): ?>
    <?php $swapJs = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'critical-css-swap.js' : 'critical-css-swap.min.js'; ?>
    <script src="/assets/js/<?= $swapJs ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php endif; ?>
  <?php $navJs = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'nav.js' : 'nav.min.js'; ?>
  <script src="/assets/js/<?= $navJs ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php if (isset($_GET['test'])): ?>
    <?php $utCss = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'usability-test.css' : 'usability-test.min.css'; ?>
    <link rel="stylesheet" href="/assets/css/<?= $utCss ?>?v=<?= ASSET_VERSION ?>">
    <?php $utJs = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? 'usability-test.js' : 'usability-test.min.js'; ?>
    <script src="/assets/js/<?= $utJs ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php endif; ?>
  <?php foreach ($pageJs ?? [] as $jsFile): ?>
    <?php $jsHref = ($_ENV['APP_ENV'] ?? 'production') === 'development' ? $jsFile : str_replace('.js', '.min.js', $jsFile); ?>
    <script src="/assets/js/<?= htmlspecialchars($jsHref) ?>?v=<?= ASSET_VERSION ?>" defer nonce="<?= CSP_NONCE ?>"></script>
  <?php endforeach; ?>

</body>

</html>