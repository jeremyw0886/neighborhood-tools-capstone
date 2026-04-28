<?php
/**
 * Create TOS Version — Super-admin form for publishing a new Terms of Service version.
 *
 * Variables from AdminController::showCreateTosVersion():
 *
 * @var array<string, string> $errors Field-keyed validation errors (version/title/effective_at/content)
 * @var array<string, mixed>  $old    Sticky values from a failed submit
 *
 * Shared data:
 *
 * @var string $csrfToken
 */

$errors    ??= [];
$old       ??= [];
$csrfToken ??= '';

$hasError = static fn(string $field): bool => isset($errors[$field]);
$errorId  = static fn(string $field): string => $field . '-error';
$oldVal   = static fn(string $field): string => htmlspecialchars($old[$field] ?? '');
?>

  <form method="post" action="/admin/tos" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <p class="required-note">Required fields are marked with <abbr title="required">*</abbr></p>

    <fieldset>
      <legend>Version Details</legend>

      <div>
        <label for="tos-version">Version <span aria-hidden="true">*</span></label>
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
        <label for="tos-title">Title <span aria-hidden="true">*</span></label>
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
        <label for="tos-effective">Effective Date <span aria-hidden="true">*</span></label>
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
        <label for="tos-content">Full Terms <span aria-hidden="true">*</span></label>
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
      <button type="submit" data-intent="success">
        <i class="fa-solid fa-check" aria-hidden="true"></i>
        Publish Version
      </button>
      <a href="/admin/tos">Cancel</a>
    </div>
  </form>
