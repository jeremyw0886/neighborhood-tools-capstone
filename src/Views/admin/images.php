<?php

$iconsSortLabels = [
    'file_name_vec'     => 'Filename',
    'uploaded_at_vec'   => 'Date',
    'assigned_category' => 'Assignment',
];

$avatarsSortLabels = [
    'file_name_avv'   => 'Filename',
    'uploaded_at_avv'  => 'Date',
    'is_active_avv'    => 'Status',
    'user_count'       => 'Users',
];

$iconsHasFilters   = $iconsSearch !== null || $iconsAssigned !== null;
$avatarsHasFilters = $avatarsSearch !== null || $avatarsStatus !== null;
?>

  <?php if ($flash): ?>
    <p role="status" data-flash><?= htmlspecialchars($flash) ?></p>
  <?php endif; ?>

  <details aria-labelledby="category-icons-heading">
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
        <button type="submit" data-intent="primary">
          <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload
        </button>
      </fieldset>
    </form>

    <form method="get" action="/admin/images" role="search" aria-label="Filter and sort category icons" data-admin-filters>
      <?php if ($avatarsPage > 1): ?>
        <input type="hidden" name="avatars_page" value="<?= $avatarsPage ?>">
      <?php endif; ?>
      <?php if ($avatarsSearch !== null): ?>
        <input type="hidden" name="avatars_q" value="<?= htmlspecialchars($avatarsSearch) ?>">
      <?php endif; ?>
      <?php if ($avatarsStatus !== null): ?>
        <input type="hidden" name="avatars_status" value="<?= htmlspecialchars($avatarsStatus) ?>">
      <?php endif; ?>
      <input type="hidden" name="avatars_sort" value="<?= htmlspecialchars($avatarsSort) ?>">
      <input type="hidden" name="avatars_dir" value="<?= htmlspecialchars(strtolower($avatarsDir)) ?>">
      <fieldset>
        <legend class="visually-hidden">Filter and sort category icons</legend>

        <div>
          <label for="icons-search">Search</label>
          <input type="search" id="icons-search" name="icons_q"
                 value="<?= htmlspecialchars($iconsSearch ?? '') ?>"
                 placeholder="Filename, description, or category…"
                 autocomplete="off"
                 data-suggest="admin" data-suggest-type="icons">
        </div>

        <div>
          <label for="icons-assigned">Assignment</label>
          <select id="icons-assigned" name="icons_assigned">
            <option value="">All</option>
            <option value="yes"<?= $iconsAssigned === 'yes' ? ' selected' : '' ?>>Assigned</option>
            <option value="no"<?= $iconsAssigned === 'no' ? ' selected' : '' ?>>Unassigned</option>
          </select>
        </div>

        <div>
          <label for="icons-sort">Sort By</label>
          <select id="icons-sort" name="icons_sort">
            <?php foreach ($iconsSortLabels as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>"<?= $iconsSort === $value ? ' selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="icons-dir">Direction</label>
          <select id="icons-dir" name="icons_dir">
            <option value="asc"<?= $iconsDir === 'ASC' ? ' selected' : '' ?>>Ascending</option>
            <option value="desc"<?= $iconsDir === 'DESC' ? ' selected' : '' ?>>Descending</option>
          </select>
        </div>

        <button type="submit" data-intent="primary" data-shape="pill">
          <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply
        </button>
        <?php if ($iconsHasFilters):
          $iconsClearParams = array_filter([
              'avatars_page'   => $avatarsPage > 1 ? (string) $avatarsPage : null,
              'avatars_q'      => $avatarsSearch,
              'avatars_status' => $avatarsStatus,
              'avatars_sort'   => $avatarsSort,
              'avatars_dir'    => strtolower($avatarsDir),
          ], static fn(?string $v): bool => $v !== null);
        ?>
          <a href="/admin/images<?= $iconsClearParams !== [] ? '?' . http_build_query($iconsClearParams) : '' ?>" role="button" data-intent="ghost">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
          </a>
        <?php endif; ?>
      </fieldset>
    </form>

    <div aria-live="polite" aria-atomic="true">
      <p>
        <strong><?= number_format($iconsTotalCount) ?></strong>
        icon<?= $iconsTotalCount !== 1 ? 's' : '' ?>
        <?php if ($iconsHasFilters): ?> matching filters<?php endif; ?>
      </p>
    </div>

    <?php if (!empty($categoryVectors)): ?>
      <div data-vector-grid role="list" aria-label="Category icon vectors">
        <?php foreach ($categoryVectors as $vec): ?>
          <article>
            <figure>
              <img src="/uploads/vectors/<?= htmlspecialchars($vec['file_name_vec']) ?>"
                   alt="<?= htmlspecialchars($vec['description_text_vec'] ?? $vec['file_name_vec']) ?>"
                   width="80" height="80"
                   loading="lazy" decoding="async">
            </figure>
            <h3 data-filename><?= htmlspecialchars($vec['file_name_vec']) ?></h3>
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
              <button type="submit" data-intent="primary" data-size="sm">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                <span class="visually-hidden">Save description</span>
              </button>
            </form>
            <div data-card-actions>
              <form method="post"
                    action="/admin/vectors/<?= (int) $vec['id_vec'] ?>/delete"
                    data-delete-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit" data-intent="danger" data-size="sm"
                  <?php if (!empty($vec['assigned_category'])): ?>
                    data-confirm="This icon is assigned to &quot;<?= htmlspecialchars($vec['assigned_category']) ?>&quot;. It will be unassigned before deletion."
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

    <?php elseif ($iconsHasFilters): ?>
      <section aria-label="No icons">
        <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
        <h3>No Icons Found</h3>
        <p>No category icons match the current filters.</p>
        <a href="/admin/images" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
        </a>
      </section>
    <?php else: ?>
      <p data-empty>No category icons uploaded yet.</p>
    <?php endif; ?>

  </details>

  <details aria-labelledby="avatar-vectors-heading">
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
        <button type="submit" data-intent="primary">
          <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload
        </button>
      </fieldset>
    </form>

    <form method="get" action="/admin/images" role="search" aria-label="Filter and sort avatar vectors" data-admin-filters>
      <?php if ($iconsPage > 1): ?>
        <input type="hidden" name="icons_page" value="<?= $iconsPage ?>">
      <?php endif; ?>
      <?php if ($iconsSearch !== null): ?>
        <input type="hidden" name="icons_q" value="<?= htmlspecialchars($iconsSearch) ?>">
      <?php endif; ?>
      <?php if ($iconsAssigned !== null): ?>
        <input type="hidden" name="icons_assigned" value="<?= htmlspecialchars($iconsAssigned) ?>">
      <?php endif; ?>
      <input type="hidden" name="icons_sort" value="<?= htmlspecialchars($iconsSort) ?>">
      <input type="hidden" name="icons_dir" value="<?= htmlspecialchars(strtolower($iconsDir)) ?>">
      <fieldset>
        <legend class="visually-hidden">Filter and sort avatar vectors</legend>

        <div>
          <label for="avatars-search">Search</label>
          <input type="search" id="avatars-search" name="avatars_q"
                 value="<?= htmlspecialchars($avatarsSearch ?? '') ?>"
                 placeholder="Filename or description…"
                 autocomplete="off"
                 data-suggest="admin" data-suggest-type="avatars">
        </div>

        <div>
          <label for="avatars-status">Status</label>
          <select id="avatars-status" name="avatars_status">
            <option value="">All</option>
            <option value="active"<?= $avatarsStatus === 'active' ? ' selected' : '' ?>>Active</option>
            <option value="inactive"<?= $avatarsStatus === 'inactive' ? ' selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div>
          <label for="avatars-sort">Sort By</label>
          <select id="avatars-sort" name="avatars_sort">
            <?php foreach ($avatarsSortLabels as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>"<?= $avatarsSort === $value ? ' selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="avatars-dir">Direction</label>
          <select id="avatars-dir" name="avatars_dir">
            <option value="asc"<?= $avatarsDir === 'ASC' ? ' selected' : '' ?>>Ascending</option>
            <option value="desc"<?= $avatarsDir === 'DESC' ? ' selected' : '' ?>>Descending</option>
          </select>
        </div>

        <button type="submit" data-intent="primary" data-shape="pill">
          <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply
        </button>
        <?php if ($avatarsHasFilters):
          $avatarsClearParams = array_filter([
              'icons_page'     => $iconsPage > 1 ? (string) $iconsPage : null,
              'icons_q'        => $iconsSearch,
              'icons_assigned' => $iconsAssigned,
              'icons_sort'     => $iconsSort,
              'icons_dir'      => strtolower($iconsDir),
          ], static fn(?string $v): bool => $v !== null);
        ?>
          <a href="/admin/images<?= $avatarsClearParams !== [] ? '?' . http_build_query($avatarsClearParams) : '' ?>" role="button" data-intent="ghost">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
          </a>
        <?php endif; ?>
      </fieldset>
    </form>

    <div aria-live="polite" aria-atomic="true">
      <p>
        <strong><?= number_format($avatarsTotalCount) ?></strong>
        avatar<?= $avatarsTotalCount !== 1 ? 's' : '' ?>
        <?php if ($avatarsHasFilters): ?> matching filters<?php endif; ?>
      </p>
    </div>

    <?php if (!empty($avatarVectors)): ?>
      <div data-vector-grid role="list" aria-label="Profile avatar vectors">
        <?php foreach ($avatarVectors as $avt): ?>
          <article <?php if (!(int) $avt['is_active_avv']): ?>data-inactive<?php endif; ?>>
            <figure>
              <img src="/uploads/vectors/<?= htmlspecialchars($avt['file_name_avv']) ?>"
                   alt="<?= htmlspecialchars($avt['description_text_avv'] ?? $avt['file_name_avv']) ?>"
                   width="80" height="80"
                   loading="lazy" decoding="async">
            </figure>
            <h3 data-filename>
              <?= htmlspecialchars($avt['file_name_avv']) ?>
              <?php if (!(int) $avt['is_active_avv']): ?>
                <span data-badge="inactive">Inactive</span>
              <?php endif; ?>
            </h3>
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
              <button type="submit" data-intent="primary" data-size="sm">
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
                  <button type="submit" data-intent="warning" data-size="sm">
                    <i class="fa-solid fa-eye-slash" aria-hidden="true"></i> Deactivate
                  </button>
                <?php else: ?>
                  <button type="submit" data-intent="success" data-size="sm">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i> Activate
                  </button>
                <?php endif; ?>
              </form>
              <form method="post"
                    action="/admin/avatar-vectors/<?= (int) $avt['id_avv'] ?>/delete"
                    data-delete-form>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit" data-intent="danger" data-size="sm"
                  <?php if ((int) $avt['user_count'] > 0): ?>
                    data-confirm="This avatar is selected by <?= (int) $avt['user_count'] ?> user<?= (int) $avt['user_count'] !== 1 ? 's' : '' ?>. It will be unassigned before deletion."
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

    <?php elseif ($avatarsHasFilters): ?>
      <section aria-label="No avatars">
        <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
        <h3>No Avatars Found</h3>
        <p>No avatar vectors match the current filters.</p>
        <a href="/admin/images" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
        </a>
      </section>
    <?php else: ?>
      <p data-empty>No avatar vectors uploaded yet.</p>
    <?php endif; ?>

  </details>
