<?php
$categories        ??= [];
$currentCategoryId ??= null;
$errors            ??= [];
$old               ??= [];
$fuelTypes         ??= [];
$images            ??= [];
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

  <nav aria-label="Related actions">
    <a href="/tools/<?= (int) $tool['id_tol'] ?>/availability">
      <i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i> Manage Availability
    </a>
  </nav>

  <?php if (!empty($errors)): ?>
    <ul role="alert" aria-label="Form errors">
      <?php foreach ($errors as $msg): ?>
        <li><?= htmlspecialchars($msg) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form id="edit-tool-form" action="/tools/<?= (int) $tool['id_tol'] ?>" method="post" novalidate>
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

    </fieldset>

  </form>

  <fieldset>
    <legend>Photos</legend>

    <p id="gallery-crop-hint">
      <i class="fa-solid fa-hand-pointer" aria-hidden="true"></i>
      Drag cards to reorder. Use <strong>Reposition</strong> to adjust cropping.
    </p>

    <?php if ($images !== []): ?>
      <ol id="gallery-manager" aria-label="Tool photos" data-tool-id="<?= (int) $tool['id_tol'] ?>">
        <?php foreach ($images as $image):
          $imgId      = (int) $image['id_tim'];
          $filename   = htmlspecialchars($image['file_name_tim']);
          $thumb      = htmlspecialchars(preg_replace('/\.(\w+)$/', '-400w.$1', $image['file_name_tim']));
          $altText    = htmlspecialchars($image['alt_text_tim'] ?? '');
          $isPrimary  = !empty($image['is_primary_tim']);
          $focalX     = (int) ($image['focal_x_tim'] ?? 50);
          $focalY     = (int) ($image['focal_y_tim'] ?? 50);
        ?>
          <li data-image-id="<?= $imgId ?>"
              data-focal-x="<?= $focalX ?>"
              data-focal-y="<?= $focalY ?>"
              draggable="true"
              tabindex="0">
            <img src="/uploads/tools/<?= $thumb ?>"
                 alt="<?= $altText !== '' ? $altText : htmlspecialchars($tool['tool_name_tol']) ?>"
                 width="400" height="268"
                 loading="lazy"
                 decoding="async"
                 <?= ($focalX !== 50 || $focalY !== 50) ? "data-focal-x=\"{$focalX}\" data-focal-y=\"{$focalY}\"" : '' ?>>

            <div>
              <label for="alt-text-<?= $imgId ?>">
                <span class="visually-hidden">Alt text for image <?= $imgId ?></span>
              </label>
              <input type="text"
                     id="alt-text-<?= $imgId ?>"
                     value="<?= $altText ?>"
                     maxlength="255"
                     placeholder="Describe this photo…"
                     data-alt-input
                     data-image-id="<?= $imgId ?>">
            </div>

            <div>
              <input type="radio"
                     name="primary_image"
                     id="primary-<?= $imgId ?>"
                     value="<?= $imgId ?>"
                     <?= $isPrimary ? 'checked' : '' ?>
                     data-primary-radio>
              <label for="primary-<?= $imgId ?>">
                <?= $isPrimary ? '<i class="fa-solid fa-star" aria-hidden="true"></i> ' : '' ?>Primary
              </label>
            </div>

            <div>
              <button type="button"
                      data-reposition
                      data-image-id="<?= $imgId ?>"
                      aria-label="Reposition this photo">
                <i class="fa-solid fa-crop-simple" aria-hidden="true"></i> Reposition
              </button>
            </div>

            <form method="post" action="/tools/<?= (int) $tool['id_tol'] ?>/images/<?= $imgId ?>" data-delete-form>
              <input type="hidden" name="_method" value="DELETE">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <button type="submit" data-intent="danger" aria-label="Delete this photo">
                <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Delete
              </button>
            </form>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <p id="gallery-empty">No photos uploaded yet.</p>
    <?php endif; ?>

    <?php if (count($images) < 6): ?>
      <form id="photo-upload-form"
            method="POST"
            action="/tools/<?= (int) $tool['id_tol'] ?>/images"
            enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <div id="photo-drop-zone">
          <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
          <p>Drag an image here or click to browse</p>
          <p id="add-photo-hint">JPEG, PNG, or WebP — max 5 MB. <?= 6 - count($images) ?> slot<?= 6 - count($images) !== 1 ? 's' : '' ?> remaining.</p>
          <label for="add-photo" class="visually-hidden">Choose a photo</label>
          <input type="file"
                 id="add-photo"
                 name="photo"
                 accept="image/jpeg,image/png,image/webp"
                 required
                 data-tool-id="<?= (int) $tool['id_tol'] ?>">
        </div>
      </form>
    <?php endif; ?>

    <?php if (isset($errors['photos'])): ?>
      <p role="alert"><?= htmlspecialchars($errors['photos']) ?></p>
    <?php endif; ?>

    <dialog id="crop-dialog" aria-label="Position your photo">
      <header>
        <h2>Position Your Photo</h2>
        <p>Drag to choose which part is visible in the 3:2 frame.</p>
      </header>
      <div id="crop-viewport" tabindex="0">
        <img id="crop-preview" alt="Crop preview" draggable="false">
        <div id="crop-frame" aria-hidden="true"></div>
      </div>
      <p id="crop-hint">Use arrow keys to nudge</p>
      <footer>
        <button type="button" data-crop-cancel>Cancel</button>
        <button type="button" data-crop-confirm data-intent="primary">
          <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
          <span data-crop-label>Upload</span>
        </button>
      </footer>
    </dialog>
  </fieldset>

  <button type="submit" form="edit-tool-form" data-intent="primary">
    <i class="fa-solid fa-check" aria-hidden="true"></i> Save Changes
  </button>

</section>
