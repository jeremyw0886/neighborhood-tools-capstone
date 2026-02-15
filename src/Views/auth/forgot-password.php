<section class="auth-page">
  <div class="auth-card">
    <header>
      <i class="fa-solid fa-key" aria-hidden="true"></i>
      <h1>Forgot Password</h1>
      <p>Enter your email and we&rsquo;ll send you a link to reset your password.</p>
    </header>

    <?php if (!empty($success)): ?>
      <div role="status" aria-live="polite" class="auth-message auth-message--success">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div role="alert" aria-live="polite" class="auth-message auth-message--error">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/forgot-password" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

      <div class="form-group">
        <label for="email">Email Address</label>
        <input
          type="email"
          id="email"
          name="email"
          required
          autocomplete="email"
          autocapitalize="none"
          spellcheck="false"
          placeholder="you@example.com"
        >
      </div>

      <button type="submit">
        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send Reset Link
      </button>
    </form>

    <footer>
      <p>Remember your password? <a href="/login">Log in</a></p>
    </footer>
  </div>
</section>
