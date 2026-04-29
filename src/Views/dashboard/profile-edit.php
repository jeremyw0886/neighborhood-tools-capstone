<?php
/**
 * Dashboard partial — edit profile form with avatar picker and personal info fields.
 *
 * Variables from ProfileController::edit():
 *
 * @var array  $profile       Editable fields from Account::getEditableProfile()
 * @var array  $preferences   All contact_preference_cpr rows for dropdown
 * @var array  $meta          Account meta key/value pairs
 * @var array  $avatarVectors Available vector avatars
 * @var array  $errors        Field-keyed validation errors (empty on first load)
 * @var array  $old           Previous input values for sticky fields (empty on first load)
 * @var string $csrfToken     CSRF token from shared data
 */

if (!empty($profile['vector_avatar'])) {
  $avatarSrc = '/uploads/vectors/' . $profile['vector_avatar'];
  $avatarAlt = $profile['vector_avatar_alt'] ?? $profile['first_name_acc'] . ' ' . $profile['last_name_acc'];
} elseif (!empty($profile['primary_image'])) {
  $avatarSrc = '/uploads/profiles/' . $profile['primary_image'];
  $avatarAlt = $profile['image_alt_text'] ?? $profile['first_name_acc'] . ' ' . $profile['last_name_acc'];
} else {
  $avatarSrc = '/assets/images/avatar-placeholder.svg';
  $avatarAlt = $profile['first_name_acc'] . ' ' . $profile['last_name_acc'];
}
?>

<?php
$errorFieldMap = [
    'first_name'         => 'first-name',
    'last_name'          => 'last-name',
    'phone'              => 'phone',
    'street_address'     => 'street-address',
    'zip_code'           => 'zip-code',
    'contact_preference' => 'contact-preference',
    'bio'                => 'bio',
    'avatar'             => 'avatar',
];
?>
<?php if (!empty($errors)): ?>
  <ul role="alert" aria-label="Form errors">
    <?php foreach ($errors as $field => $msg): ?>
      <?php $anchor = $errorFieldMap[$field] ?? ''; ?>
      <li>
        <?php if ($anchor !== ''): ?>
          <a href="#<?= $anchor ?>"><?= htmlspecialchars($msg) ?></a>
        <?php else: ?>
          <?= htmlspecialchars($msg) ?>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form action="/profile/edit" method="post" enctype="multipart/form-data" novalidate>
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
  <fieldset>
    <legend>Account</legend>

    <div>
      <label for="username">Username</label>
      <input type="text"
        id="username"
        readonly
        aria-describedby="username-hint"
        value="<?= htmlspecialchars($profile['username_acc']) ?>"
        tabindex="-1">
      <p id="username-hint">Cannot be changed</p>
    </div>

    <div>
      <label for="email">Email Address</label>
      <input type="email"
        id="email"
        readonly
        aria-describedby="email-hint"
        value="<?= htmlspecialchars($profile['email_address_acc']) ?>"
        tabindex="-1">
      <p id="email-hint">Cannot be changed</p>
    </div>
  </fieldset>

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
        <?php if (isset($errors['first_name'])): ?>aria-invalid="true" aria-describedby="first-name-error" <?php endif; ?>>
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
        <?php if (isset($errors['last_name'])): ?>aria-invalid="true" aria-describedby="last-name-error" <?php endif; ?>>
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
        <?php if (isset($errors['phone'])): ?>aria-invalid="true" aria-describedby="phone-error" <?php endif; ?>>
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
        <?php if (isset($errors['street_address'])): ?>aria-invalid="true" aria-describedby="street-address-error" <?php endif; ?>>
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
        <?php if (isset($errors['zip_code'])): ?>aria-invalid="true" aria-describedby="zip-code-error" <?php endif; ?>>
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
        <?php if (isset($errors['contact_preference'])): ?>aria-invalid="true" aria-describedby="contact-pref-error" <?php endif; ?>>
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
  </fieldset>

  <fieldset>
    <legend>Your Avatar</legend>

    <?php
      $anyImage    = $profile['primary_image'] ?? ($profile['stored_image'] ?? null);
      $imageFile   = $anyImage;
      $photoActive = !empty($profile['primary_image']);
      $hasVector   = !empty($profile['id_avv_acc']);
      $avatarSrcsets ??= null;
    ?>

    <div data-profile-photo-upload>
      <?php if ($anyImage !== null): ?>
        <?php
          $photoSrc = '/uploads/profiles/' . htmlspecialchars($anyImage);
          $photoAlt = $profile['image_alt_text'] ?? $profile['first_name_acc'] . ' ' . $profile['last_name_acc'];
          $editFocalX = (int) ($profile['focal_x_aim'] ?? $profile['stored_focal_x'] ?? 50);
          $editFocalY = (int) ($profile['focal_y_aim'] ?? $profile['stored_focal_y'] ?? 50);
          $editFocalAttrs = ($editFocalX !== 50 || $editFocalY !== 50)
              ? " data-focal-x=\"{$editFocalX}\" data-focal-y=\"{$editFocalY}\""
              : '';
          $editIsWebp = str_ends_with($anyImage, '.webp');
        ?>
        <figure>
          <?php if ($avatarSrcsets !== null): ?>
            <picture>
              <?php if (!$editIsWebp && $avatarSrcsets['avifSrcset'] !== ''): ?>
                <source type="image/avif"
                        srcset="<?= htmlspecialchars($avatarSrcsets['avifSrcset']) ?>"
                        sizes="120px">
              <?php endif; ?>
              <?php if (!$editIsWebp && $avatarSrcsets['webpSrcset'] !== ''): ?>
                <source type="image/webp"
                        srcset="<?= htmlspecialchars($avatarSrcsets['webpSrcset']) ?>"
                        sizes="120px">
              <?php endif; ?>
              <img src="<?= htmlspecialchars(\App\Core\ViewHelper::uploadVersion($photoSrc)) ?>"
                srcset="<?= htmlspecialchars($avatarSrcsets['srcset']) ?>"
                sizes="120px"
                alt="<?= htmlspecialchars($photoAlt) ?>"
                width="120" height="120"
                loading="lazy" decoding="async"<?= $editFocalAttrs ?>>
            </picture>
          <?php else: ?>
            <img src="<?= $photoSrc ?>"
              alt="<?= htmlspecialchars($photoAlt) ?>"
              width="120" height="120"
              loading="lazy" decoding="async"<?= $editFocalAttrs ?>>
          <?php endif; ?>
        </figure>
      <?php endif; ?>
      <div>
        <label for="avatar"><?= $anyImage !== null ? 'Replace photo' : 'Upload a photo' ?></label>
        <input type="file"
          id="avatar"
          name="avatar"
          accept="image/jpeg,image/png,image/webp"
          <?php if (isset($errors['avatar'])): ?>aria-invalid="true" aria-describedby="avatar-error" <?php endif; ?>>
        <?php if (isset($errors['avatar'])): ?>
          <p id="avatar-error" role="alert"><?= htmlspecialchars($errors['avatar']) ?></p>
        <?php endif; ?>
        <?php if ($anyImage !== null): ?>
          <div>
            <button type="button" data-reposition-photo>
              <i class="fa-solid fa-crop" aria-hidden="true"></i> Reposition
            </button>
            <button type="button" data-remove-photo>
              <i class="fa-solid fa-trash" aria-hidden="true"></i> Remove photo
            </button>
          </div>
        <?php endif; ?>
      </div>
      <input type="hidden" name="focal_x" value="<?= (int) ($profile['focal_x_aim'] ?? $profile['stored_focal_x'] ?? 50) ?>">
      <input type="hidden" name="focal_y" value="<?= (int) ($profile['focal_y_aim'] ?? $profile['stored_focal_y'] ?? 50) ?>">
    </div>

    <?php
      $hasAnyPhoto = $anyImage !== null;
      $photoThumbSrc = '';
      if ($hasAnyPhoto) {
          $thumbName = pathinfo($anyImage, PATHINFO_FILENAME);
          $thumbExt  = pathinfo($anyImage, PATHINFO_EXTENSION);
          $photoThumbSrc = '/uploads/profiles/' . $thumbName . '-80w.' . $thumbExt;
      }
      $isNoneSelected = !$photoActive && !$hasVector;
    ?>
    <?php if ($hasAnyPhoto || $avatarVectors !== []): ?>
      <p data-avatar-heading>Choose your display avatar</p>
      <div data-avatar-grid role="radiogroup" aria-label="Display avatar selection">

        <?php if ($hasAnyPhoto): ?>
          <label data-photo-choice>
            <input type="radio"
              name="avatar_vector"
              value="photo"
              <?= $photoActive && !$hasVector ? 'checked' : '' ?>>
            <span>
              <img src="<?= htmlspecialchars(\App\Core\ViewHelper::uploadVersion($photoThumbSrc)) ?>"
                alt="My photo"
                width="64" height="64"
                decoding="async">
            </span>
            <small>My Photo</small>
          </label>
        <?php endif; ?>

        <?php foreach ($avatarVectors as $av): ?>
          <label>
            <input type="radio"
              name="avatar_vector"
              value="<?= (int) $av['id_avv'] ?>"
              <?= (int) ($profile['id_avv_acc'] ?? 0) === (int) $av['id_avv'] ? 'checked' : '' ?>>
            <span>
              <img src="/uploads/vectors/<?= htmlspecialchars($av['file_name_avv']) ?>"
                alt="<?= htmlspecialchars($av['description_text_avv'] ?? 'Avatar option') ?>"
                width="64" height="64"
                decoding="async">
            </span>
          </label>
        <?php endforeach; ?>

        <label>
          <input type="radio"
            name="avatar_vector"
            value="none"
            <?= $isNoneSelected ? 'checked' : '' ?>>
          <span>
            <img src="/assets/images/avatar-placeholder.svg"
              alt="Default avatar"
              width="64" height="64"
              decoding="async">
          </span>
          <small>Default</small>
        </label>

      </div>
    <?php endif; ?>
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

  <button type="submit" data-intent="primary">
    <i class="fa-solid fa-check" aria-hidden="true"></i> Save Changes
  </button>

</form>


<dialog id="crop-dialog" aria-label="Position your photo">
  <header>
    <h2>Position Your Photo</h2>
    <p>Drag to choose which part is visible in the square frame.</p>
  </header>
  <div id="crop-viewport" tabindex="0">
    <img id="crop-preview" src="data:," alt="Crop preview" draggable="false">
    <div id="crop-frame" aria-hidden="true"></div>
  </div>
  <p id="crop-hint">Use arrow keys to nudge</p>
  <footer>
    <button type="button" data-crop-cancel>Cancel</button>
    <button type="button" data-crop-confirm data-intent="primary">
      <i class="fa-solid fa-camera" aria-hidden="true"></i>
      <span data-crop-label>Set Photo</span>
    </button>
  </footer>
</dialog>
