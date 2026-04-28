<?php
/**
 * Reset-password form — submit a new password using a tokenized link.
 *
 * Variables from AuthController::showResetPassword():
 *
 * @var string                 $token            One-time reset token from the email link
 * @var array<string, string>  $errors           Field-keyed validation errors (general/password/password_confirm)
 * @var string                 $turnstileSiteKey Cloudflare Turnstile site key (empty if disabled)
 *
 * Shared data:
 *
 * @var string $csrfToken
 */

$token            ??= '';
$errors           ??= [];
$turnstileSiteKey ??= '';
$csrfToken        ??= '';
?>
<section class="auth-page">
  <div class="auth-card">
    <header>
      <i class="fa-solid fa-lock" aria-hidden="true"></i>
      <h1>Reset Password</h1>
      <p>Choose a new password for your account.</p>
    </header>

    <?php $generalError = $errors['general'] ?? null; ?>

    <?php if (!empty($generalError)): ?>
      <div role="alert" aria-live="polite" data-flash="error">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <?= htmlspecialchars($generalError) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/reset-password" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <div aria-hidden="true">
        <label for="website">Leave this empty</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <p class="required-note">Required fields are marked with <abbr title="required">*</abbr></p>

      <div class="form-group">
        <label for="password">New Password</label>
        <input
          type="password"
          id="password"
          name="password"
          required
          autocomplete="new-password"
          minlength="8"
          maxlength="72"
          placeholder="At least 8 characters"
          aria-describedby="password-hint<?= !empty($errors['password']) ? ' password-error' : '' ?>"
          <?= !empty($errors['password']) ? 'aria-invalid="true"' : '' ?>
        >
        <span id="password-hint" class="form-hint">Must be 8&ndash;72 characters.</span>
        <?php if (!empty($errors['password'])): ?>
          <span id="password-error" role="alert"><?= htmlspecialchars($errors['password']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="password_confirm">Confirm New Password</label>
        <input
          type="password"
          id="password_confirm"
          name="password_confirm"
          required
          autocomplete="new-password"
          minlength="8"
          maxlength="72"
          placeholder="Re-enter your password"
          aria-describedby="<?= !empty($errors['password_confirm']) ? 'password-confirm-error' : '' ?>"
          <?= !empty($errors['password_confirm']) ? 'aria-invalid="true"' : '' ?>
        >
        <?php if (!empty($errors['password_confirm'])): ?>
          <span id="password-confirm-error" role="alert"><?= htmlspecialchars($errors['password_confirm']) ?></span>
        <?php endif; ?>
      </div>

      <?php if (!empty($turnstileSiteKey)): ?>
        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>" data-action="reset_password"></div>
      <?php endif; ?>

      <button type="submit" data-intent="primary" data-size="lg" data-width="full">
        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Reset Password
      </button>
    </form>

    <footer>
      <p>Remember your password? <a href="/login">Log In</a></p>
    </footer>
  </div>
</section>
