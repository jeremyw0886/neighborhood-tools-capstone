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

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
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
        <label for="tool-condition">Condition <span aria-hidden="true">*</span></label>
        <?php $selectedCondition = $old['condition'] ?? strtolower($tool['tool_condition'] ?? 'good'); ?>
        <select id="tool-condition"
                name="condition"
                required
                <?php if (isset($errors['condition'])): ?>aria-invalid="true" aria-describedby="tool-condition-error"<?php endif; ?>>
          <option value="new" <?= $selectedCondition === 'new' ? 'selected' : '' ?>>New</option>
          <option value="good" <?= $selectedCondition === 'good' ? 'selected' : '' ?>>Good</option>
          <option value="fair" <?= $selectedCondition === 'fair' ? 'selected' : '' ?>>Fair</option>
          <option value="poor" <?= $selectedCondition === 'poor' ? 'selected' : '' ?>>Poor</option>
        </select>
        <?php if (isset($errors['condition'])): ?>
          <p id="tool-condition-error" role="alert"><?= htmlspecialchars($errors['condition']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="tool-duration">Loan Duration (hours)</label>
        <input type="number"
               id="tool-duration"
               name="loan_duration"
               min="1"
               max="720"
               step="1"
               value="<?= htmlspecialchars($old['loan_duration'] ?? (string) ((int) ($tool['default_loan_duration_hours_tol'] ?? 168))) ?>"
               <?php if (isset($errors['loan_duration'])): ?>aria-invalid="true" aria-describedby="tool-duration-error"<?php endif; ?>>
        <?php if (isset($errors['loan_duration'])): ?>
          <p id="tool-duration-error" role="alert"><?= htmlspecialchars($errors['loan_duration']) ?></p>
        <?php endif; ?>
      </div>

      <?php
        $fuelChecked = isset($old['uses_fuel'])
            ? !empty($old['uses_fuel'])
            : !empty($tool['fuel_type']);
        $selectedFuel = $old['fuel_type'] ?? ($tool['fuel_type'] ?? '');
      ?>
      <div>
        <label>
          <input type="checkbox"
                 id="uses-fuel"
                 name="uses_fuel"
                 value="1"
                 <?= $fuelChecked ? 'checked' : '' ?>>
          Uses Fuel
        </label>
      </div>

      <div id="fuel-type-group">
        <label for="fuel-type">Fuel Type</label>
        <select id="fuel-type"
                name="fuel_type"
                <?php if (isset($errors['fuel_type'])): ?>aria-invalid="true" aria-describedby="fuel-type-error"<?php endif; ?>>
          <option value="">Select fuel type</option>
          <?php foreach ($fuelTypes as $type): ?>
            <option value="<?= htmlspecialchars($type) ?>"
              <?= $selectedFuel === $type ? 'selected' : '' ?>>
              <?= htmlspecialchars(ucwords($type, '-/')) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['fuel_type'])): ?>
          <p id="fuel-type-error" role="alert"><?= htmlspecialchars($errors['fuel_type']) ?></p>
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
