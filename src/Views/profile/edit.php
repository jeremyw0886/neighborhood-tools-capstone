<?php
/**
 * Edit Profile — pre-filled form for updating account details.
 *
 * Variables from ProfileController::edit():
 *   $profile      array   Editable fields from Account::getEditableProfile()
 *   $preferences  array   All contact_preference_cpr rows for dropdown
 *   $meta         array   Account meta key/value pairs
 *   $errors       array   Field-keyed validation errors (empty on first load)
 *   $old          array   Previous input values for sticky fields (empty on first load)
 *   $csrfToken    string  CSRF token from shared data
 */

$avatarSrc = $profile['primary_image']
    ? '/uploads/profiles/' . $profile['primary_image']
    : '/assets/images/avatar-placeholder.svg';

$avatarAlt = $profile['image_alt_text']
    ?? $profile['first_name_acc'] . ' ' . $profile['last_name_acc'];
?>

<section id="profile-edit" aria-labelledby="edit-profile-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <header>
    <h1 id="edit-profile-heading">
      <i class="fa-solid fa-user-pen" aria-hidden="true"></i> Edit Profile
    </h1>
    <p>Update your personal information and profile details.</p>
  </header>

  <?php if (!empty($errors)): ?>
    <ul role="alert" aria-label="Form errors">
      <?php foreach ($errors as $msg): ?>
        <li><?= htmlspecialchars($msg) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form action="/profile/edit" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <fieldset>
      <legend>Personal Information</legend>

      <div>
        <label for="first-name">First Name <span aria-hidden="true">*</span></label>
        <input type="text"
               id="first-name"
               name="first_name"
               required
               maxlength="100"
               autocomplete="given-name"
               value="<?= htmlspecialchars($old['first_name'] ?? $profile['first_name_acc']) ?>"
               <?php if (isset($errors['first_name'])): ?>aria-invalid="true" aria-describedby="first-name-error"<?php endif; ?>>
        <?php if (isset($errors['first_name'])): ?>
          <p id="first-name-error" role="alert"><?= htmlspecialchars($errors['first_name']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="last-name">Last Name <span aria-hidden="true">*</span></label>
        <input type="text"
               id="last-name"
               name="last_name"
               required
               maxlength="100"
               autocomplete="family-name"
               value="<?= htmlspecialchars($old['last_name'] ?? $profile['last_name_acc']) ?>"
               <?php if (isset($errors['last_name'])): ?>aria-invalid="true" aria-describedby="last-name-error"<?php endif; ?>>
        <?php if (isset($errors['last_name'])): ?>
          <p id="last-name-error" role="alert"><?= htmlspecialchars($errors['last_name']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="phone">Phone Number</label>
        <input type="tel"
               id="phone"
               name="phone"
               maxlength="20"
               autocomplete="tel"
               placeholder="e.g. (828) 555-0123"
               value="<?= htmlspecialchars($old['phone'] ?? $profile['phone_number_acc'] ?? '') ?>"
               <?php if (isset($errors['phone'])): ?>aria-invalid="true" aria-describedby="phone-error"<?php endif; ?>>
        <?php if (isset($errors['phone'])): ?>
          <p id="phone-error" role="alert"><?= htmlspecialchars($errors['phone']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="street-address">Street Address</label>
        <input type="text"
               id="street-address"
               name="street_address"
               maxlength="255"
               autocomplete="street-address"
               placeholder="e.g. 123 Mountain View Dr"
               value="<?= htmlspecialchars($old['street_address'] ?? $profile['street_address_acc'] ?? '') ?>"
               <?php if (isset($errors['street_address'])): ?>aria-invalid="true" aria-describedby="street-address-error"<?php endif; ?>>
        <?php if (isset($errors['street_address'])): ?>
          <p id="street-address-error" role="alert"><?= htmlspecialchars($errors['street_address']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="zip-code">ZIP Code <span aria-hidden="true">*</span></label>
        <input type="text"
               id="zip-code"
               name="zip_code"
               required
               maxlength="10"
               pattern="\d{5}(-\d{4})?"
               inputmode="numeric"
               autocomplete="postal-code"
               placeholder="e.g. 28801"
               value="<?= htmlspecialchars($old['zip_code'] ?? $profile['zip_code_acc']) ?>"
               <?php if (isset($errors['zip_code'])): ?>aria-invalid="true" aria-describedby="zip-code-error"<?php endif; ?>>
        <?php if (isset($errors['zip_code'])): ?>
          <p id="zip-code-error" role="alert"><?= htmlspecialchars($errors['zip_code']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <label for="contact-preference">Contact Preference <span aria-hidden="true">*</span></label>
        <?php $selectedPref = $old['contact_preference'] ?? $profile['preference_name_cpr']; ?>
        <select id="contact-preference"
                name="contact_preference"
                required
                <?php if (isset($errors['contact_preference'])): ?>aria-invalid="true" aria-describedby="contact-pref-error"<?php endif; ?>>
          <?php foreach ($preferences as $pref): ?>
            <option value="<?= htmlspecialchars($pref['preference_name_cpr']) ?>"
              <?= $selectedPref === $pref['preference_name_cpr'] ? 'selected' : '' ?>>
              <?= htmlspecialchars(ucfirst($pref['preference_name_cpr'])) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['contact_preference'])): ?>
          <p id="contact-pref-error" role="alert"><?= htmlspecialchars($errors['contact_preference']) ?></p>
        <?php endif; ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>About You</legend>

      <div>
        <label for="bio">Bio</label>
        <textarea id="bio"
                  name="bio"
                  rows="5"
                  maxlength="2000"
                  placeholder="Tell your neighbors a bit about yourself…"><?= htmlspecialchars($old['bio'] ?? $profile['bio_text_abi'] ?? '') ?></textarea>
      </div>

      <div>
        <label for="avatar">Profile Photo</label>
        <?php if ($profile['primary_image']): ?>
          <figure>
            <img src="<?= htmlspecialchars($avatarSrc) ?>"
                 alt="<?= htmlspecialchars($avatarAlt) ?>"
                 width="150" height="150"
                 decoding="async">
            <figcaption>Current photo — upload a new file to replace it.</figcaption>
          </figure>
        <?php endif; ?>
        <input type="file"
               id="avatar"
               name="avatar"
               accept="image/jpeg,image/png,image/webp"
               <?php if (isset($errors['avatar'])): ?>aria-invalid="true" aria-describedby="avatar-error"<?php endif; ?>>
        <?php if (isset($errors['avatar'])): ?>
          <p id="avatar-error" role="alert"><?= htmlspecialchars($errors['avatar']) ?></p>
        <?php endif; ?>
      </div>
    </fieldset>

    <?php if ($meta !== []): ?>
      <fieldset>
        <legend>Additional Details</legend>
        <dl>
          <?php foreach ($meta as $item): ?>
            <dt><?= htmlspecialchars(ucwords(str_replace('_', ' ', $item['meta_key_acm']))) ?></dt>
            <dd><?= htmlspecialchars($item['meta_value_acm']) ?></dd>
          <?php endforeach; ?>
        </dl>
      </fieldset>
    <?php endif; ?>

    <button type="submit">
      <i class="fa-solid fa-check" aria-hidden="true"></i> Save Changes
    </button>
  </form>

</section>
