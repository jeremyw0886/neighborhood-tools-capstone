<?php
/**
 * Edit Tool — pre-filled form for updating an existing tool listing.
 *
 * Variables from ToolController::edit():
 *   $tool              array   Full row from tool_detail_v + owner_avatar
 *   $categories        array   Rows from category_summary_v
 *   $currentCategoryId ?int    Current category ID for dropdown pre-selection
 *   $errors            array   Field-keyed validation errors (empty on first load)
 *   $old               array   Previous input values from failed update (empty on first load)
 *   $csrfToken         string  CSRF token from shared data
 */
?>

<section aria-labelledby="edit-tool-heading">

  <nav aria-label="Breadcrumb">
    <ol>
      <li>
        <a href="/tools/<?= (int) $tool['id_tol'] ?>">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Tool
        </a>
      </li>
    </ol>
  </nav>

  <header>
    <h1 id="edit-tool-heading">
      <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit Tool
    </h1>
    <p>Update the details for <strong><?= htmlspecialchars($tool['tool_name_tol']) ?></strong>.</p>
  </header>

  <?php if (!empty($errors)): ?>
    <ul role="alert" aria-label="Form errors">
      <?php foreach ($errors as $msg): ?>
        <li><?= htmlspecialchars($msg) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form action="/tools/<?= (int) $tool['id_tol'] ?>" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <fieldset>
      <legend>Tool Details</legend>

      <div>
        <label for="tool-name">Tool Name <span aria-hidden="true">*</span></label>
        <input type="text"
               id="tool-name"
               name="tool_name"
               required
               maxlength="100"
               autocomplete="off"
               value="<?= htmlspecialchars($old['tool_name'] ?? $tool['tool_name_tol']) ?>"
               <?php if (isset($errors['tool_name'])): ?>aria-invalid="true" aria-describedby="tool-name-error"<?php endif; ?>>
        <?php if (isset($errors['tool_name'])): ?>
          <p id="tool-name-error" role="alert"><?= htmlspecialchars($errors['tool_name']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="tool-description">Description</label>
        <textarea id="tool-description"
                  name="description"
                  rows="4"
                  maxlength="1000"><?= htmlspecialchars($old['description'] ?? $tool['tool_description_tol'] ?? '') ?></textarea>
      </div>

      <div>
        <label for="tool-category">Category <span aria-hidden="true">*</span></label>
        <?php $selectedCat = (int) ($old['category_id'] ?? $currentCategoryId ?? 0); ?>
        <select id="tool-category"
                name="category_id"
                required
                <?php if (isset($errors['category_id'])): ?>aria-invalid="true" aria-describedby="tool-category-error"<?php endif; ?>>
          <option value="">Select a category</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id_cat'] ?>"
              <?= $selectedCat === (int) $cat['id_cat'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['category_name_cat']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['category_id'])): ?>
          <p id="tool-category-error" role="alert"><?= htmlspecialchars($errors['category_id']) ?></p>
        <?php endif; ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Listing Options</legend>

      <div>
        <label for="tool-fee">Rental Fee ($/day) <span aria-hidden="true">*</span></label>
        <input type="number"
               id="tool-fee"
               name="rental_fee"
               required
               min="0"
               max="9999"
               step="0.50"
               value="<?= htmlspecialchars($old['rental_fee'] ?? number_format((float) $tool['rental_fee_tol'], 2, '.', '')) ?>"
               <?php if (isset($errors['rental_fee'])): ?>aria-invalid="true" aria-describedby="tool-fee-error"<?php endif; ?>>
        <?php if (isset($errors['rental_fee'])): ?>
          <p id="tool-fee-error" role="alert"><?= htmlspecialchars($errors['rental_fee']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="tool-image">Tool Photo</label>
        <?php if (!empty($tool['primary_image'])): ?>
          <figure>
            <img src="/uploads/tools/<?= htmlspecialchars($tool['primary_image']) ?>"
                 alt="Current photo of <?= htmlspecialchars($tool['tool_name_tol']) ?>"
                 width="200" height="133"
                 decoding="async">
            <figcaption>Current photo — upload a new file to replace it.</figcaption>
          </figure>
        <?php endif; ?>
        <input type="file"
               id="tool-image"
               name="tool_image"
               accept="image/jpeg,image/png,image/webp"
               <?php if (isset($errors['tool_image'])): ?>aria-invalid="true" aria-describedby="tool-image-error"<?php endif; ?>>
        <?php if (isset($errors['tool_image'])): ?>
          <p id="tool-image-error" role="alert"><?= htmlspecialchars($errors['tool_image']) ?></p>
        <?php endif; ?>
      </div>
    </fieldset>

    <button type="submit">
      <i class="fa-solid fa-check" aria-hidden="true"></i> Save Changes
    </button>
  </form>

</section>
