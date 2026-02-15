<section class="auth-page">
  <div class="auth-card">
    <header>
      <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
      <h1>Log In</h1>
      <p>Welcome back! Sign in to manage your tools and borrows.</p>
    </header>

    <?php if (!empty($authSuccess)): ?>
      <div role="status" aria-live="polite" class="auth-message auth-message--success">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <?= htmlspecialchars($authSuccess) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div role="alert" aria-live="polite" class="auth-message auth-message--error">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/login" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

      <!-- Honeypot — hidden from real users, attracts bots -->
      <div aria-hidden="true" style="position:absolute;left:-9999px">
        <label for="website">Leave this empty</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
      </div>

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

      <button type="submit">
        <i class="fa-solid fa-mountain-sun" aria-hidden="true"></i> Log In
      </button>
    </form>

    <footer>
      <p>Don't have an account? <a href="/register">Sign up</a></p>
    </footer>
  </div>
</section>
