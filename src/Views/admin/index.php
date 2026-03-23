<?php
$sectionId = $adminSectionId;
?>

<section aria-labelledby="<?= $sectionId ?>">

  <header data-admin-header>
    <h1 id="<?= $sectionId ?>">
      <i class="<?= $adminIcon ?>" aria-hidden="true"></i>
      <?= htmlspecialchars($adminHeading) ?>
    </h1>
    <?php if ($adminDescription): ?>
      <p><?= htmlspecialchars($adminDescription) ?></p>
    <?php endif; ?>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-search.php'; ?>

  <div data-admin-body>
    <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

    <div data-admin-content tabindex="-1">
      <?php require $adminPartial; ?>
    </div>
  </div>

</section>
