<?php
/**
 * Forgot-password form — request a reset link by email.
 *
 * Variables from AuthController::showForgotPassword():
 *
 * @var ?string $success          Generic success flash (always shown post-submit)
 * @var ?string $error            Error flash (Turnstile, format, rate limit)
 * @var string  $oldEmail         Sticky email after format/Turnstile failure
 * @var string  $turnstileSiteKey Cloudflare Turnstile site key (empty if disabled)
 *
 * Shared data:
 *
 * @var string  $csrfToken
 */

$success          ??= null;
$error            ??= null;
$oldEmail         ??= '';
$turnstileSiteKey ??= '';
$csrfToken        ??= '';
?>
<section class="auth-page">
  <div class="auth-card">
    <header>
      <i class="fa-solid fa-key" aria-hidden="true"></i>
      <h1>Forgot Password</h1>
      <p>Enter your email and we&rsquo;ll send you a link to reset your password.</p>
    </header>

    <?php if (!empty($success)): ?>
      <div role="status" aria-live="polite" data-flash="success">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div role="alert" aria-live="polite" data-flash="error">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/forgot-password" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

      <div aria-hidden="true">
        <label for="website">Leave this empty</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <p class="required-note">Required fields are marked with <abbr title="required">*</abbr></p>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input
          type="email"
          id="email"
          name="email"
          value="<?= htmlspecialchars($oldEmail ?? '') ?>"
          required
          autocomplete="email"
          autocapitalize="none"
          spellcheck="false"
          placeholder="you@example.com"
          <?= !empty($error) ? 'aria-invalid="true"' : '' ?>
        >
      </div>

      <?php if (!empty($turnstileSiteKey)): ?>
        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>" data-action="forgot_password"></div>
      <?php endif; ?>

      <button type="submit" data-intent="primary" data-size="lg" data-width="full">
        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send Reset Link
      </button>
    </form>

    <footer>
      <p>Remember your password? <a href="/login">Log In</a></p>
    </footer>
  </div>
</section>
