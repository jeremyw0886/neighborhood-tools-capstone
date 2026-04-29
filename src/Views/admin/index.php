<?php
/**
 * Admin shell — wraps every admin page with the section header, search, side
 *  nav, and the inner partial named by $adminPartial.
 *
 * Variables from BaseController::renderAdmin():
 *
 * @var string  $adminSectionId    DOM id used on the <section> aria-labelledby and the <h1>
 * @var string  $adminIcon         Font Awesome class for the heading icon
 * @var string  $adminHeading      Heading text
 * @var ?string $adminDescription  Sub-heading paragraph; falsy hides the <p>
 * @var string  $adminPartial      Absolute filesystem path of the inner partial
 */

$sectionId = $adminSectionId;
?>

<section aria-labelledby="<?= $sectionId ?>">

  <header data-admin-header>
    <h1 id="<?= $sectionId ?>">
      <i class="<?= $adminIcon ?>" aria-hidden="true"></i>
      <span data-heading-text><?= htmlspecialchars($adminHeading) ?></span>
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
