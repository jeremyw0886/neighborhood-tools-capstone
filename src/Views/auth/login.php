<?php
/**
 * Login form — username/email + password with Turnstile and honeypot.
 *
 * Variables from AuthController::showLogin():
 *
 * @var ?string $error            Auth error flash (invalid creds, suspended, etc.)
 * @var ?string $authSuccess      Success flash (e.g. password just reset)
 * @var string  $oldUsername      Previously-entered username for sticky field
 * @var string  $turnstileSiteKey Cloudflare Turnstile site key (empty if disabled)
 *
 * Shared data:
 *
 * @var string  $csrfToken
 */

$error            ??= null;
$authSuccess      ??= null;
$oldUsername      ??= '';
$turnstileSiteKey ??= '';
$csrfToken        ??= '';
?>
<section class="auth-page">
  <div class="auth-card">
    <header>
      <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
      <h1>Log In</h1>
      <p>Welcome back! Sign in to manage your tools and borrows.</p>
      <p id="login-hint">You can use either your username or email address.</p>
    </header>

    <?php if (!empty($authSuccess)): ?>
      <div role="status" aria-live="polite" data-flash="success">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <?= htmlspecialchars($authSuccess) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div role="alert" aria-live="polite" data-flash="error">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/login" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <div aria-hidden="true">
        <label for="website">Leave this empty</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <p class="required-note">Required fields are marked with <abbr title="required">*</abbr></p>

      <div class="form-group">
        <label for="username">Username or Email</label>
        <input
          type="text"
          id="username"
          name="username"
          value="<?= htmlspecialchars($oldUsername ?? '') ?>"
          required
          autofocus
          autocomplete="username"
          autocapitalize="none"
          spellcheck="false"
          aria-describedby="login-hint"
          placeholder="Username or email address"
        >
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          required
          autocomplete="current-password"
          minlength="8"
          placeholder="Enter your password"
        >
        <a href="/forgot-password" class="forgot-link">Forgot your password?</a>
      </div>

      <?php if (!empty($turnstileSiteKey)): ?>
        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>" data-action="login"></div>
      <?php endif; ?>

      <button type="submit" data-intent="primary" data-size="lg" data-width="full">
        <i class="fa-solid fa-mountain-sun" aria-hidden="true"></i> Log In
      </button>
    </form>

    <footer>
      <p>Don't have an account? <a href="/register">Sign up</a></p>
    </footer>
  </div>
</section>
