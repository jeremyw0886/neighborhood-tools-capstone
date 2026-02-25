<?php
/**
 * Admin — Create new TOS version (Super Admin only).
 *
 * Variables from AdminController::showCreateTos():
 *   $errors  array  Keyed by field name
 *   $old     array  Sticky values (version, title, content, summary, effectiveAt)
 *
 * Shared data:
 *   $csrfToken  string
 */

$hasError = static fn(string $field): bool => isset($errors[$field]);

$errorId = static fn(string $field): string => $field . '-error';

$oldVal = static fn(string $field): string =>
    htmlspecialchars($old[$field] ?? '');
?>

<section aria-labelledby="admin-tos-create-heading">

  <header>
    <h1 id="admin-tos-create-heading">
      <i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i>
      Create TOS Version
    </h1>
    <p>Publish a new Terms of Service version. The current active version will be superseded.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <form method="post" action="/admin/tos" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <fieldset>
      <legend>Version Details</legend>

      <div>
        <label for="tos-version">Version</label>
        <input type="text"
               id="tos-version"
               name="version"
               value="<?= $oldVal('version') ?>"
               maxlength="20"
               placeholder="e.g. 2.0"
               required
               <?= $hasError('version') ? 'aria-invalid="true" aria-describedby="' . $errorId('version') . '"' : '' ?>>
        <?php if ($hasError('version')): ?>
          <span id="<?= $errorId('version') ?>" role="alert"><?= htmlspecialchars($errors['version']) ?></span>
        <?php endif; ?>
      </div>

      <div>
        <label for="tos-title">Title</label>
        <input type="text"
               id="tos-title"
               name="title"
               value="<?= $oldVal('title') ?>"
               maxlength="255"
               placeholder="e.g. Updated Terms of Service"
               required
               <?= $hasError('title') ? 'aria-invalid="true" aria-describedby="' . $errorId('title') . '"' : '' ?>>
        <?php if ($hasError('title')): ?>
          <span id="<?= $errorId('title') ?>" role="alert"><?= htmlspecialchars($errors['title']) ?></span>
        <?php endif; ?>
      </div>

      <div>
        <label for="tos-effective">Effective Date</label>
        <input type="date"
               id="tos-effective"
               name="effective_at"
               value="<?= $oldVal('effectiveAt') ?>"
               required
               <?= $hasError('effective_at') ? 'aria-invalid="true" aria-describedby="' . $errorId('effective_at') . '"' : '' ?>>
        <?php if ($hasError('effective_at')): ?>
          <span id="<?= $errorId('effective_at') ?>" role="alert"><?= htmlspecialchars($errors['effective_at']) ?></span>
        <?php endif; ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Content</legend>

      <div>
        <label for="tos-summary">Summary</label>
        <textarea id="tos-summary"
                  name="summary"
                  rows="3"
                  placeholder="Plain-language summary of key changes (optional)"><?= $oldVal('summary') ?></textarea>
      </div>

      <div>
        <label for="tos-content">Full Terms</label>
        <textarea id="tos-content"
                  name="content"
                  rows="16"
                  required
                  <?= $hasError('content') ? 'aria-invalid="true" aria-describedby="' . $errorId('content') . '"' : '' ?>
                  placeholder="Full Terms of Service text..."><?= $oldVal('content') ?></textarea>
        <?php if ($hasError('content')): ?>
          <span id="<?= $errorId('content') ?>" role="alert"><?= htmlspecialchars($errors['content']) ?></span>
        <?php endif; ?>
      </div>
    </fieldset>

    <div>
      <button type="submit">
        <i class="fa-solid fa-check" aria-hidden="true"></i>
        Publish Version
      </button>
      <a href="/admin/tos">Cancel</a>
    </div>
  </form>

</div>
</section>
