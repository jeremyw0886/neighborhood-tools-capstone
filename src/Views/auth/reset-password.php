<section class="auth-page">
  <div class="auth-card">
    <header>
      <i class="fa-solid fa-lock" aria-hidden="true"></i>
      <h1>Reset Password</h1>
      <p>Choose a new password for your account.</p>
    </header>

    <?php if (!empty($error)): ?>
      <div role="alert" aria-live="polite" class="auth-message auth-message--error">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/reset-password" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

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
        >
        <span class="form-hint">Must be 8&ndash;72 characters.</span>
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
        >
      </div>

      <button type="submit">
        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Reset Password
      </button>
    </form>

    <footer>
      <p>Remember your password? <a href="/login">Log in</a></p>
    </footer>
  </div>
</section>
