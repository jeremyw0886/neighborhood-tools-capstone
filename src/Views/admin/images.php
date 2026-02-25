<?php
/**
 * Admin — Image management (category icons + avatar vectors).
 *
 * Variables from AdminController::images():
 *   $categoryVectors     array   Paginated category icon rows
 *   $avatarVectors       array   Paginated avatar vector rows
 *   $flash               ?string
 *   $iconsPage           int     Current category icons page
 *   $iconsTotalPages     int     Total category icons pages
 *   $iconsTotalCount     int     Total category icon count
 *   $iconsFilterParams   array   Filter params for icon pagination links
 *   $avatarsPage         int     Current avatar vectors page
 *   $avatarsTotalPages   int     Total avatar vectors pages
 *   $avatarsTotalCount   int     Total avatar vector count
 *   $avatarsFilterParams array   Filter params for avatar pagination links
 */
?>

<section aria-labelledby="admin-images-heading">

  <header>
    <h1 id="admin-images-heading">
      <i class="fa-solid fa-images" aria-hidden="true"></i>
      Manage Images
    </h1>
    <p>Upload and manage category icons and profile avatar vectors.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <?php if ($flash): ?>
    <p role="status" data-flash><?= htmlspecialchars($flash) ?></p>
  <?php endif; ?>

  <details open aria-labelledby="category-icons-heading">
    <summary>
      <h2 id="category-icons-heading">
        <i class="fa-solid fa-tags" aria-hidden="true"></i>
        Category Icons
        <i class="fa-solid fa-chevron-down" aria-hidden="true" data-chevron></i>
      </h2>
    </summary>

    <form method="post"
          action="/admin/vectors"
          enctype="multipart/form-data"
          data-upload-form>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <fieldset>
        <legend>Upload Category Icon</legend>
        <div>
          <label for="cat-vector-file">SVG File</label>
          <input type="file"
                 id="cat-vector-file"
                 name="vector_file"
                 accept=".svg"
                 required>
          <small>SVG only, max 1 MB</small>
        </div>
        <div>
          <label for="cat-vector-desc">Description</label>
          <input type="text"
                 id="cat-vector-desc"
                 name="description"
                 maxlength="255"
                 placeholder="e.g. Hammer tool icon">
        </div>
        <button type="submit">
          <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload
        </button>
      </fieldset>
    </form>

    <?php if (!empty($categoryVectors)): ?>
      <div data-vector-grid role="list" aria-label="Category icon vectors">
        <?php foreach ($categoryVectors as $vec): ?>
          <article role="listitem">
            <figure>
              <img src="/uploads/vectors/<?= htmlspecialchars($vec['file_name_vec']) ?>"
                   alt="<?= htmlspecialchars($vec['description_text_vec'] ?? $vec['file_name_vec']) ?>"
                   width="80" height="80"
                   loading="lazy" decoding="async">
            </figure>
            <p data-filename><?= htmlspecialchars($vec['file_name_vec']) ?></p>
            <p data-meta>
              <time datetime="<?= htmlspecialchars($vec['uploaded_at_vec']) ?>">
                <?= htmlspecialchars(date('M j, Y', strtotime($vec['uploaded_at_vec']))) ?>
              </time>
              by <?= htmlspecialchars($vec['first_name_acc'] . ' ' . $vec['last_name_acc']) ?>
            </p>
            <?php if (!empty($vec['assigned_category'])): ?>
              <p data-assigned><?= htmlspecialchars($vec['assigned_category']) ?></p>
            <?php endif; ?>
            <form method="post"
                  action="/admin/vectors/<?= (int) $vec['id_vec'] ?>/description"
                  data-inline-form>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <label for="vec-desc-<?= (int) $vec['id_vec'] ?>" class="visually-hidden">Description</label>
              <input type="text"
                     id="vec-desc-<?= (int) $vec['id_vec'] ?>"
                     name="description"
                     maxlength="255"
                     value="<?= htmlspecialchars($vec['description_text_vec'] ?? '') ?>"
                     placeholder="Add description">
              <button type="submit">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                <span class="visually-hidden">Save description</span>
              </button>
            </form>
            <div data-card-actions>
              <form method="post"
                    action="/admin/vectors/<?= (int) $vec['id_vec'] ?>/delete"
                    data-delete-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit"
                  <?php if (!empty($vec['assigned_category'])): ?>
                    disabled aria-disabled="true" title="Assigned to <?= htmlspecialchars($vec['assigned_category']) ?>"
                  <?php endif; ?>>
                  <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Delete
                </button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php
        $basePath     = '/admin/images';
        $filterParams = $iconsFilterParams;
        $page         = $iconsPage;
        $totalPages   = $iconsTotalPages;
        $pageParam    = 'icons_page';
        require BASE_PATH . '/src/Views/partials/pagination.php';
      ?>

    <?php else: ?>
      <p data-empty>No category icons uploaded yet.</p>
    <?php endif; ?>

  </details>

  <details open aria-labelledby="avatar-vectors-heading">
    <summary>
      <h2 id="avatar-vectors-heading">
        <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
        Profile Avatars
        <i class="fa-solid fa-chevron-down" aria-hidden="true" data-chevron></i>
      </h2>
    </summary>

    <form method="post"
          action="/admin/avatar-vectors"
          enctype="multipart/form-data"
          data-upload-form>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <fieldset>
        <legend>Upload Avatar Vector</legend>
        <div>
          <label for="avt-vector-file">SVG File</label>
          <input type="file"
                 id="avt-vector-file"
                 name="vector_file"
                 accept=".svg"
                 required>
          <small>SVG only, max 1 MB</small>
        </div>
        <div>
          <label for="avt-vector-desc">Description</label>
          <input type="text"
                 id="avt-vector-desc"
                 name="description"
                 maxlength="255"
                 placeholder="e.g. Mountain hiker avatar">
        </div>
        <button type="submit">
          <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload
        </button>
      </fieldset>
    </form>

    <?php if (!empty($avatarVectors)): ?>
      <div data-vector-grid role="list" aria-label="Profile avatar vectors">
        <?php foreach ($avatarVectors as $avt): ?>
          <article role="listitem" <?php if (!(int) $avt['is_active_avv']): ?>data-inactive<?php endif; ?>>
            <figure>
              <img src="/uploads/vectors/<?= htmlspecialchars($avt['file_name_avv']) ?>"
                   alt="<?= htmlspecialchars($avt['description_text_avv'] ?? $avt['file_name_avv']) ?>"
                   width="80" height="80"
                   loading="lazy" decoding="async">
            </figure>
            <p data-filename>
              <?= htmlspecialchars($avt['file_name_avv']) ?>
              <?php if (!(int) $avt['is_active_avv']): ?>
                <span data-badge="inactive">Inactive</span>
              <?php endif; ?>
            </p>
            <p data-meta>
              <time datetime="<?= htmlspecialchars($avt['uploaded_at_avv']) ?>">
                <?= htmlspecialchars(date('M j, Y', strtotime($avt['uploaded_at_avv']))) ?>
              </time>
              by <?= htmlspecialchars($avt['first_name_acc'] . ' ' . $avt['last_name_acc']) ?>
            </p>
            <div data-stats>
              <span data-status="<?= (int) $avt['is_active_avv'] ? 'active' : 'inactive' ?>">
                <?= (int) $avt['is_active_avv'] ? 'Active' : 'Inactive' ?>
              </span>
              <span data-users><?= (int) $avt['user_count'] ?> user<?= (int) $avt['user_count'] !== 1 ? 's' : '' ?></span>
            </div>
            <form method="post"
                  action="/admin/avatar-vectors/<?= (int) $avt['id_avv'] ?>/description"
                  data-inline-form>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <label for="avt-desc-<?= (int) $avt['id_avv'] ?>" class="visually-hidden">Description</label>
              <input type="text"
                     id="avt-desc-<?= (int) $avt['id_avv'] ?>"
                     name="description"
                     maxlength="255"
                     value="<?= htmlspecialchars($avt['description_text_avv'] ?? '') ?>"
                     placeholder="Add description">
              <button type="submit">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                <span class="visually-hidden">Save description</span>
              </button>
            </form>
            <div data-card-actions>
              <form method="post"
                    action="/admin/avatar-vectors/<?= (int) $avt['id_avv'] ?>/toggle"
                    data-toggle-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <?php if ((int) $avt['is_active_avv']): ?>
                  <button type="submit">
                    <i class="fa-solid fa-eye-slash" aria-hidden="true"></i> Deactivate
                  </button>
                <?php else: ?>
                  <button type="submit">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i> Activate
                  </button>
                <?php endif; ?>
              </form>
              <form method="post"
                    action="/admin/avatar-vectors/<?= (int) $avt['id_avv'] ?>/delete"
                    data-delete-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit"
                  <?php if ((int) $avt['user_count'] > 0): ?>
                    disabled aria-disabled="true" title="Selected by <?= (int) $avt['user_count'] ?> user(s)"
                  <?php endif; ?>>
                  <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Delete
                </button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php
        $basePath     = '/admin/images';
        $filterParams = $avatarsFilterParams;
        $page         = $avatarsPage;
        $totalPages   = $avatarsTotalPages;
        $pageParam    = 'avatars_page';
        require BASE_PATH . '/src/Views/partials/pagination.php';
      ?>

    <?php else: ?>
      <p data-empty>No avatar vectors uploaded yet.</p>
    <?php endif; ?>

  </details>

</div>
</section>
