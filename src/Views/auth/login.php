<section class="auth-page">
  <div class="auth-card">
    <header>
      <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
      <h1>Log In</h1>
      <p>Welcome back! Sign in to manage your tools and borrows.</p>
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

      <div class="form-group">
        <label for="username">Username</label>
        <input
          type="text"
          id="username"
          name="username"
          value="<?= htmlspecialchars($oldUsername ?? '') ?>"
          required
          autocomplete="username"
          autocapitalize="none"
          spellcheck="false"
          placeholder="your_username"
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

      <button type="submit" data-intent="primary" data-size="lg" data-width="full">
        <i class="fa-solid fa-mountain-sun" aria-hidden="true"></i> Log In
      </button>

      <?php if (!empty($turnstileSiteKey)): ?>
        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>" data-action="login" data-appearance="interaction-only" data-theme="light" data-callback="onTurnstileVerify" data-expired-callback="onTurnstileExpire" data-error-callback="onTurnstileError"></div>
      <?php endif; ?>
    </form>

    <footer>
      <p>Don't have an account? <a href="/register">Sign up</a></p>
    </footer>
  </div>
</section>
