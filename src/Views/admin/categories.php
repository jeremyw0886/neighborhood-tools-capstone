<?php
/**
 * Admin — Category icon assignment (sortable, filterable).
 *
 * Variables from AdminController::categories():
 *   $categories   array   Rows from Category::getAllWithIconsFiltered()
 *   $vectors      array   Rows from VectorImage::getAll()
 *   $flash        ?string
 *   $totalCount   int     Total categories matching filters
 *   $search       ?string Active search query
 *   $hasIcon      ?string Active icon filter ('yes'|'no'|null)
 *   $sort         string  Active sort column
 *   $dir          string  Active sort direction (ASC|DESC)
 *   $filterParams array   Non-null filter params for pagination links
 */

$sortLabels = [
    'category_name_cat' => 'Category Name',
    'file_name_vec'     => 'Icon Filename',
];

$sortToColumn = [
    'category_name_cat' => 0,
    'file_name_vec'     => 1,
];

$ariaSortDir = $dir === 'ASC' ? 'ascending' : 'descending';
$hasFilters  = $search !== null || $hasIcon !== null;
?>

<section aria-labelledby="admin-categories-heading">

  <header>
    <h1 id="admin-categories-heading">
      <i class="fa-solid fa-tags" aria-hidden="true"></i>
      Manage Categories
    </h1>
    <p>Assign icons to tool categories. <a href="/admin/images">Manage images</a></p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <?php if ($flash): ?>
    <p role="status" data-flash><?= htmlspecialchars($flash) ?></p>
  <?php endif; ?>

  <form method="get" action="/admin/categories" role="search" aria-label="Filter and sort categories" data-admin-filters>
    <fieldset>
      <legend class="visually-hidden">Filter and sort categories</legend>

      <div>
        <label for="cats-search">Search</label>
        <input type="search" id="cats-search" name="q"
               value="<?= htmlspecialchars($search ?? '') ?>"
               placeholder="Category name…"
               autocomplete="off">
      </div>

      <div>
        <label for="cats-icon">Icon</label>
        <select id="cats-icon" name="icon">
          <option value="">All</option>
          <option value="yes"<?= $hasIcon === 'yes' ? ' selected' : '' ?>>Has Icon</option>
          <option value="no"<?= $hasIcon === 'no' ? ' selected' : '' ?>>No Icon</option>
        </select>
      </div>

      <div>
        <label for="cats-sort">Sort By</label>
        <select id="cats-sort" name="sort">
          <?php foreach ($sortLabels as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"<?= $sort === $value ? ' selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="cats-dir">Direction</label>
        <select id="cats-dir" name="dir">
          <option value="asc"<?= $dir === 'ASC' ? ' selected' : '' ?>>Ascending</option>
          <option value="desc"<?= $dir === 'DESC' ? ' selected' : '' ?>>Descending</option>
        </select>
      </div>

      <button type="submit">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply
      </button>
    </fieldset>
  </form>

  <div aria-live="polite" aria-atomic="true">
    <p>
      <strong><?= number_format($totalCount) ?></strong>
      categor<?= $totalCount !== 1 ? 'ies' : 'y' ?>
      <?php if ($hasFilters): ?> matching filters<?php endif; ?>
    </p>
  </div>

  <section aria-labelledby="category-icons-heading">
    <h2 id="category-icons-heading" class="visually-hidden">Category Icon Assignment</h2>

    <?php if (!empty($categories)): ?>
      <table>
        <caption class="visually-hidden">Tool categories with icon assignments</caption>
        <thead>
          <tr>
            <?php
            $columns = ['Category', 'Current Icon', 'Assign Icon'];
            foreach ($columns as $i => $label):
              $isSorted = isset($sortToColumn[$sort]) && $sortToColumn[$sort] === $i;
            ?>
              <th scope="col"<?= $isSorted ? ' aria-sort="' . $ariaSortDir . '"' : '' ?>><?= $label ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td data-label="Category"><?= htmlspecialchars($cat['category_name_cat']) ?></td>
              <td data-label="Current Icon">
                <?php if (!empty($cat['file_name_vec'])): ?>
                  <figure data-icon-preview>
                    <img src="/uploads/vectors/<?= htmlspecialchars($cat['file_name_vec']) ?>"
                         alt="<?= htmlspecialchars($cat['description_text_vec'] ?? '') ?>"
                         width="32" height="32"
                         loading="lazy" decoding="async">
                  </figure>
                <?php else: ?>
                  <span data-none>None</span>
                <?php endif; ?>
              </td>
              <td data-label="Assign Icon">
                <form method="post"
                      action="/admin/categories/<?= (int) $cat['id_cat'] ?>/icon"
                      data-icon-form>
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <select name="vector_id"
                          aria-label="Icon for <?= htmlspecialchars($cat['category_name_cat']) ?>">
                    <option value="">None</option>
                    <?php foreach ($vectors as $vec): ?>
                      <option value="<?= (int) $vec['id_vec'] ?>"
                        <?= (int) ($cat['id_vec_cat'] ?? 0) === (int) $vec['id_vec'] ? ' selected' : '' ?>>
                        <?= htmlspecialchars($vec['description_text_vec'] ?? $vec['file_name_vec']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Save
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php elseif ($hasFilters): ?>
      <section aria-label="No categories">
        <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
        <h3>No Categories Found</h3>
        <p>No categories match the current filters.</p>
        <a href="/admin/categories" role="button">
          <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Clear Filters
        </a>
      </section>
    <?php else: ?>
      <p data-empty>No categories found.</p>
    <?php endif; ?>

  </section>

</div>
</section>
