<section class="auth-page">
  <div class="auth-card">
    <header>
      <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
      <h1>Create an Account</h1>
      <p>Join your neighbors and start sharing tools today.</p>
    </header>

    <?php if (!empty($errors['general'])): ?>
      <div role="alert" aria-live="polite" class="auth-message auth-message--error">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <?= htmlspecialchars($errors['general']) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/register" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

      <!-- Honeypot -->
      <div aria-hidden="true" style="position:absolute;left:-9999px">
        <label for="reg-website">Leave this empty</label>
        <input type="text" id="reg-website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <fieldset>
        <legend>Personal Information</legend>

        <div class="form-row">
          <div class="form-group">
            <label for="first_name">First Name</label>
            <input
              type="text"
              id="first_name"
              name="first_name"
              value="<?= htmlspecialchars($old['first_name'] ?? '') ?>"
              required
              maxlength="50"
              autocomplete="given-name"
              placeholder="First name"
              aria-describedby="<?= !empty($errors['first_name']) ? 'first-name-error' : '' ?>"
              <?= !empty($errors['first_name']) ? 'aria-invalid="true"' : '' ?>
            >
            <?php if (!empty($errors['first_name'])): ?>
              <span id="first-name-error" role="alert"><?= htmlspecialchars($errors['first_name']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input
              type="text"
              id="last_name"
              name="last_name"
              value="<?= htmlspecialchars($old['last_name'] ?? '') ?>"
              required
              maxlength="50"
              autocomplete="family-name"
              placeholder="Last name"
              aria-describedby="<?= !empty($errors['last_name']) ? 'last-name-error' : '' ?>"
              <?= !empty($errors['last_name']) ? 'aria-invalid="true"' : '' ?>
            >
            <?php if (!empty($errors['last_name'])): ?>
              <span id="last-name-error" role="alert"><?= htmlspecialchars($errors['last_name']) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <div class="form-group">
          <label for="reg-email">Email Address</label>
          <input
            type="email"
            id="reg-email"
            name="email"
            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
            required
            autocomplete="email"
            autocapitalize="none"
            spellcheck="false"
            placeholder="you@example.com"
            aria-describedby="<?= !empty($errors['email']) ? 'email-error' : '' ?>"
            <?= !empty($errors['email']) ? 'aria-invalid="true"' : '' ?>
          >
          <?php if (!empty($errors['email'])): ?>
            <span id="email-error" role="alert"><?= htmlspecialchars($errors['email']) ?></span>
          <?php endif; ?>
        </div>
      </fieldset>

      <fieldset>
        <legend>Security</legend>

        <div class="form-group">
          <label for="reg-password">Password</label>
          <input
            type="password"
            id="reg-password"
            name="password"
            required
            minlength="8"
            maxlength="72"
            autocomplete="new-password"
            placeholder="At least 8 characters"
            aria-describedby="password-hint<?= !empty($errors['password']) ? ' password-error' : '' ?>"
            <?= !empty($errors['password']) ? 'aria-invalid="true"' : '' ?>
          >
          <span id="password-hint" class="form-hint">Must be 8–72 characters</span>
          <?php if (!empty($errors['password'])): ?>
            <span id="password-error" role="alert"><?= htmlspecialchars($errors['password']) ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="password_confirm">Confirm Password</label>
          <input
            type="password"
            id="password_confirm"
            name="password_confirm"
            required
            minlength="8"
            maxlength="72"
            autocomplete="new-password"
            placeholder="Re-enter your password"
            aria-describedby="<?= !empty($errors['password_confirm']) ? 'password-confirm-error' : '' ?>"
            <?= !empty($errors['password_confirm']) ? 'aria-invalid="true"' : '' ?>
          >
          <?php if (!empty($errors['password_confirm'])): ?>
            <span id="password-confirm-error" role="alert"><?= htmlspecialchars($errors['password_confirm']) ?></span>
          <?php endif; ?>
        </div>
      </fieldset>

      <fieldset>
        <legend>Location</legend>

        <div class="form-group">
          <label for="neighborhood_id">Neighborhood</label>
          <select id="neighborhood_id" name="neighborhood_id" autocomplete="address-level3">
            <option value="">— Select your neighborhood (optional) —</option>
            <?php
              $currentCity = '';
              foreach ($neighborhoods as $nbh):
                  $city = $nbh['city_name_nbh'] ?? 'Other';
                  if ($city !== $currentCity):
                      if ($currentCity !== ''):
                          echo '</optgroup>';
                      endif;
                      $currentCity = $city;
            ?>
              <optgroup label="<?= htmlspecialchars($city) ?>">
            <?php endif; ?>
                <option
                  value="<?= (int) $nbh['id_nbh'] ?>"
                  <?= ((int) ($old['neighborhood_id'] ?? 0)) === (int) $nbh['id_nbh'] ? 'selected' : '' ?>
                ><?= htmlspecialchars($nbh['neighborhood_name_nbh']) ?></option>
            <?php endforeach; ?>
            <?php if ($currentCity !== ''): ?>
              </optgroup>
            <?php endif; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="zip_code">Zip Code</label>
          <input
            type="text"
            id="zip_code"
            name="zip_code"
            value="<?= htmlspecialchars($old['zip_code'] ?? '') ?>"
            required
            pattern="\d{5}"
            maxlength="5"
            inputmode="numeric"
            autocomplete="postal-code"
            placeholder="28801"
            aria-describedby="<?= !empty($errors['zip_code']) ? 'zip-error' : '' ?>"
            <?= !empty($errors['zip_code']) ? 'aria-invalid="true"' : '' ?>
          >
          <?php if (!empty($errors['zip_code'])): ?>
            <span id="zip-error" role="alert"><?= htmlspecialchars($errors['zip_code']) ?></span>
          <?php endif; ?>
        </div>
      </fieldset>

      <button type="submit">
        <i class="fa-solid fa-mountain" aria-hidden="true"></i> Create Account
      </button>
    </form>

    <footer>
      <p>Already have an account? <a href="/login">Log in</a></p>
    </footer>
  </div>
</section>
