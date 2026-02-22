<?php
/**
 * Browse Categories — view all tool categories with stats.
 *
 * Variables from CategoryController::index():
 *   $categories  array  Rows from category_summary_v (id_cat, category_name_cat,
 *                       category_icon, total_tools, listed_tools, available_tools,
 *                       category_avg_rating, min_rental_fee, max_rental_fee)
 */
?>

<section id="categories-page" aria-labelledby="categories-heading">

  <header>
    <h1 id="categories-heading">
      <i class="fa-solid fa-tags" aria-hidden="true"></i> Browse Categories
    </h1>
    <nav aria-label="Browse mode">
      <a href="/categories" aria-current="page">
        <i class="fa-solid fa-tags" aria-hidden="true"></i> Categories
      </a>
      <a href="/tools">
        <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> All Tools
      </a>
    </nav>
  </header>

  <?php if (!empty($categories)): ?>

    <div role="list">
      <?php foreach ($categories as $cat):
        $available  = (int) $cat['available_tools'];
        $total      = (int) $cat['total_tools'];
        $minFee     = $cat['min_rental_fee'] !== null ? number_format((float) $cat['min_rental_fee'], 2) : null;
        $maxFee     = $cat['max_rental_fee'] !== null ? number_format((float) $cat['max_rental_fee'], 2) : null;
        $hasIcon    = !empty($cat['category_icon']);
      ?>
        <article role="listitem">
          <a href="/tools?category=<?= (int) $cat['id_cat'] ?>">

            <figure aria-hidden="true">
              <?php if ($hasIcon): ?>
                <img src="/uploads/vectors/<?= htmlspecialchars($cat['category_icon']) ?>"
                     alt=""
                     width="64" height="64"
                     loading="lazy" decoding="async">
              <?php else: ?>
                <i class="fa-solid fa-toolbox"></i>
              <?php endif; ?>
            </figure>

            <h2><?= htmlspecialchars($cat['category_name_cat']) ?></h2>

            <dl>
              <dt><i class="fa-solid fa-check-circle" aria-hidden="true"></i> Available</dt>
              <dd><?= $available ?> of <?= $total ?> tool<?= $total !== 1 ? 's' : '' ?></dd>

              <?php if ($minFee !== null): ?>
                <dt><i class="fa-solid fa-dollar-sign" aria-hidden="true"></i> Fee Range</dt>
                <dd>$<?= htmlspecialchars($minFee) ?><?= $minFee !== $maxFee ? ' – $' . htmlspecialchars($maxFee) : '' ?></dd>
              <?php endif; ?>
            </dl>

            <span>
              Browse <?= htmlspecialchars($cat['category_name_cat']) ?>
              <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </span>

          </a>
        </article>
      <?php endforeach; ?>
    </div>

  <?php else: ?>

    <section aria-label="No categories">
      <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
      <h2>No Categories Yet</h2>
      <p>Tool categories will appear here once they are created.</p>
      <a href="/tools" role="button">
        <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Browse All Tools
      </a>
    </section>

  <?php endif; ?>

</section>
