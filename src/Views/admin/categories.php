<?php
/**
 * Admin — Category & vector image management.
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
    <p>Upload SVG icons and assign them to tool categories.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <?php if ($flash): ?>
    <p role="status" data-flash><?= htmlspecialchars($flash) ?></p>
  <?php endif; ?>

  <section aria-labelledby="vector-library-heading">
    <h2 id="vector-library-heading">
      <i class="fa-solid fa-image" aria-hidden="true"></i>
      Vector Image Library
    </h2>

    <form method="post"
          action="/admin/vectors"
          enctype="multipart/form-data"
          data-upload-form>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <fieldset>
        <legend>Upload New Icon</legend>
        <div>
          <label for="vector-file">SVG File</label>
          <input type="file"
                 id="vector-file"
                 name="vector_file"
                 accept=".svg"
                 required>
          <small>SVG only, max 1 MB</small>
        </div>
        <div>
          <label for="vector-desc">Description</label>
          <input type="text"
                 id="vector-desc"
                 name="description"
                 maxlength="255"
                 placeholder="e.g. Hammer tool icon">
        </div>
        <button type="submit">
          <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload
        </button>
      </fieldset>
    </form>

    <?php if (!empty($vectors)): ?>
      <div data-vector-grid role="list" aria-label="Uploaded vector images">
        <?php foreach ($vectors as $vec): ?>
          <article role="listitem">
            <figure>
              <img src="/uploads/vectors/<?= htmlspecialchars($vec['file_name_vec']) ?>"
                   alt="<?= htmlspecialchars($vec['description_text_vec'] ?? $vec['file_name_vec']) ?>"
                   width="48" height="48"
                   loading="lazy" decoding="async">
            </figure>
            <dl>
              <dt>File</dt>
              <dd><?= htmlspecialchars($vec['file_name_vec']) ?></dd>
              <?php if (!empty($vec['description_text_vec'])): ?>
                <dt>Description</dt>
                <dd><?= htmlspecialchars($vec['description_text_vec']) ?></dd>
              <?php endif; ?>
              <dt>Uploaded</dt>
              <dd>
                <time datetime="<?= htmlspecialchars($vec['uploaded_at_vec']) ?>">
                  <?= htmlspecialchars(date('M j, Y', strtotime($vec['uploaded_at_vec']))) ?>
                </time>
              </dd>
              <dt>By</dt>
              <dd><?= htmlspecialchars($vec['first_name_acc'] . ' ' . $vec['last_name_acc']) ?></dd>
              <?php if (!empty($vec['assigned_category'])): ?>
                <dt>Assigned to</dt>
                <dd data-assigned><?= htmlspecialchars($vec['assigned_category']) ?></dd>
              <?php endif; ?>
            </dl>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p data-empty>No vector images uploaded yet.</p>
    <?php endif; ?>

  </section>

  <section aria-labelledby="category-icons-heading">
    <h2 id="category-icons-heading">
      <i class="fa-solid fa-palette" aria-hidden="true"></i>
      Category Icon Assignment
    </h2>

    <?php if (!empty($categories)): ?>
      <table>
        <thead>
          <tr>
            <th scope="col">Category</th>
            <th scope="col">Current Icon</th>
            <th scope="col">Assign Icon</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td><?= htmlspecialchars($cat['category_name_cat']) ?></td>
              <td>
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
              <td>
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
              </td>
              <td>
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

</section>
