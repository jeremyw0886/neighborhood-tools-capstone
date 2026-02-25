<?php
/**
 * Admin — Category icon assignment.
 *
 * Variables from AdminController::categories():
 *   $categories  array  Rows from Category::getAllWithIcons()
 *   $vectors     array  Rows from VectorImage::getAll()
 *   $flash       ?string
 */
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

  <section aria-labelledby="category-icons-heading">
    <h2 id="category-icons-heading">
      <i class="fa-solid fa-palette" aria-hidden="true"></i>
      Category Icon Assignment
    </h2>

    <?php if (!empty($categories)): ?>
      <table>
        <caption class="visually-hidden">Tool categories with icon assignments</caption>
        <thead>
          <tr>
            <th scope="col">Category</th>
            <th scope="col">Current Icon</th>
            <th scope="col">Assign Icon</th>
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
    <?php else: ?>
      <p data-empty>No categories found.</p>
    <?php endif; ?>

  </section>

</div>
</section>
