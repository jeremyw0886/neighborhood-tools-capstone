<?php
/**
 * List a Tool — tool creation form.
 *
 * Variables from ToolController::create():
 *   $categories  array  Rows from category_summary_v
 *   $csrfToken   string CSRF token from shared data
 */
?>

<section aria-labelledby="create-tool-heading">

  <header>
    <h1 id="create-tool-heading">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> List a Tool
    </h1>
    <p>Share your tools with your neighbors. Fill out the details below to get started.</p>
  </header>

  <form action="/tools" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div>
      <label for="tool-name">Tool Name <span aria-hidden="true">*</span></label>
      <input type="text"
             id="tool-name"
             name="tool_name"
             required
             maxlength="100"
             placeholder="e.g. DeWalt 20V Cordless Drill"
             autocomplete="off">
    </div>

    <div>
      <label for="tool-description">Description</label>
      <textarea id="tool-description"
                name="description"
                rows="4"
                maxlength="1000"
                placeholder="Describe the tool's features, included accessories, any usage tips…"></textarea>
    </div>

    <div>
      <label for="tool-category">Category <span aria-hidden="true">*</span></label>
      <select id="tool-category" name="category_id" required>
        <option value="">Select a category</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int) $cat['id_cat'] ?>">
            <?= htmlspecialchars($cat['category_name_cat']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="tool-fee">Rental Fee ($/day) <span aria-hidden="true">*</span></label>
      <input type="number"
             id="tool-fee"
             name="rental_fee"
             required
             min="0"
             max="9999"
             step="0.01"
             placeholder="0.00">
    </div>

    <div>
      <label for="tool-image">Tool Photo</label>
      <input type="file"
             id="tool-image"
             name="tool_image"
             accept="image/jpeg,image/png,image/webp">
    </div>

    <p><em>Full tool listing functionality is coming soon. This form is a preview of what's ahead.</em></p>

    <button type="submit" disabled>
      <i class="fa-solid fa-check" aria-hidden="true"></i> List Tool (Coming Soon)
    </button>
  </form>

</section>
