<?php
$categories ??= [];
$errors     ??= [];
$old        ??= [];
$fuelTypes  ??= [];
?>

<section aria-labelledby="create-tool-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <header>
    <h1 id="create-tool-heading">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> List a Tool
    </h1>
    <p>Share your tools with your neighbors. Fill out the details below to get started.</p>
  </header>

  <?php if (!empty($errors)): ?>
    <ul role="alert" aria-label="Form errors">
      <?php foreach ($errors as $msg): ?>
        <li><?= htmlspecialchars($msg) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form action="/tools" method="post" enctype="multipart/form-data" novalidate>
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
               placeholder="e.g. DeWalt 20V Cordless Drill"
               autocomplete="off"
               value="<?= htmlspecialchars($old['tool_name'] ?? '') ?>"
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
                  maxlength="1000"
                  placeholder="Describe the tool's features, included accessories, any usage tips…"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
      </div>

      <div>
        <label for="tool-category">Category <span aria-hidden="true">*</span></label>
        <select id="tool-category"
                name="category_id"
                required
                <?php if (isset($errors['category_id'])): ?>aria-invalid="true" aria-describedby="tool-category-error"<?php endif; ?>>
          <option value="">Select a category</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id_cat'] ?>"
              <?= ((int) ($old['category_id'] ?? 0)) === (int) $cat['id_cat'] ? 'selected' : '' ?>>
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
               value="<?= htmlspecialchars($old['rental_fee'] ?? '0.00') ?>"
               <?php if (isset($errors['rental_fee'])): ?>aria-invalid="true" aria-describedby="tool-fee-error"<?php endif; ?>>
        <?php if (isset($errors['rental_fee'])): ?>
          <p id="tool-fee-error" role="alert"><?= htmlspecialchars($errors['rental_fee']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="tool-condition">Condition <span aria-hidden="true">*</span></label>
        <?php $selectedCondition = $old['condition'] ?? 'good'; ?>
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
               value="<?= htmlspecialchars($old['loan_duration'] ?? '168') ?>"
               <?php if (isset($errors['loan_duration'])): ?>aria-invalid="true" aria-describedby="tool-duration-error"<?php endif; ?>>
        <?php if (isset($errors['loan_duration'])): ?>
          <p id="tool-duration-error" role="alert"><?= htmlspecialchars($errors['loan_duration']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label>
          <input type="checkbox"
                 id="uses-fuel"
                 name="uses_fuel"
                 value="1"
                 <?= !empty($old['uses_fuel']) ? 'checked' : '' ?>>
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
              <?= ($old['fuel_type'] ?? '') === $type ? 'selected' : '' ?>>
              <?= htmlspecialchars(ucwords($type, '-/')) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['fuel_type'])): ?>
          <p id="fuel-type-error" role="alert"><?= htmlspecialchars($errors['fuel_type']) ?></p>
        <?php endif; ?>
      </div>

    </fieldset>

    <fieldset>
      <legend>Photos</legend>

      <p id="gallery-crop-hint">
        <i class="fa-solid fa-hand-pointer" aria-hidden="true"></i>
        Use <strong>Reposition</strong> to adjust cropping. The first photo will be your primary listing image.
      </p>

      <ol id="photo-queue" hidden aria-label="Queued photo previews"></ol>

      <div id="photo-drop-zone">
        <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
        <p>Drag an image here or click to browse</p>
        <p id="photo-queue-hint">JPEG, PNG, or WebP — max 5 MB each. 6 slots remaining.</p>
        <label for="tool-photos" class="visually-hidden">Choose a photo</label>
        <input type="file"
               id="tool-photos"
               name="photos[]"
               accept="image/jpeg,image/png,image/webp"
               <?php if (isset($errors['photos'])): ?>aria-invalid="true"<?php endif; ?>>
      </div>
      <?php if (isset($errors['photos'])): ?>
        <p role="alert"><?= htmlspecialchars($errors['photos']) ?></p>
      <?php endif; ?>

      <div id="photo-queue-data" hidden></div>

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
            <i class="fa-solid fa-plus"></i>
            <span data-crop-label>Add</span>
          </button>
        </footer>
      </dialog>
    </fieldset>

    <button type="submit" data-intent="success">
      <i class="fa-solid fa-check" aria-hidden="true"></i> List Tool
    </button>

    <?php if (!empty($turnstileSiteKey)): ?>
      <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>" data-action="tool_create" data-appearance="interaction-only" data-theme="light" data-callback="onTurnstileVerify" data-expired-callback="onTurnstileExpire" data-error-callback="onTurnstileError"></div>
    <?php endif; ?>
  </form>

</section>
