<?php
/**
 * List a Tool — tool creation form.
 *
 * Variables from ToolController::create():
 *   $categories  array   Rows from category_summary_v
 *   $errors      array   Field-keyed validation errors (empty on first load)
 *   $old         array   Previous input values for sticky fields (empty on first load)
 *   $csrfToken   string  CSRF token from shared data
 */
?>

<section aria-labelledby="create-tool-heading">

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
        <label for="tool-image">Tool Photo</label>
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
      <i class="fa-solid fa-check" aria-hidden="true"></i> List Tool
    </button>
  </form>

</section>
