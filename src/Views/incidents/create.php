<?php
/**
 * Report an Incident — incident creation form for a borrow transaction.
 *
 * Variables from IncidentController::create():
 *   $borrow        array   Row from Borrow::findById() (borrower/lender/tool context)
 *   $hasExisting   bool    Whether an open incident already exists for this borrow
 *   $incidentTypes array   Rows from Incident::getTypes() (id_ity, type_name_ity)
 *   $errors        array   Field-keyed validation errors (empty on first load)
 *   $old           array   Previous input values for sticky fields (empty on first load)
 *
 * Shared data:
 *   $authUser   array{id, name, first_name, role, avatar}
 *   $csrfToken  string
 */

$borrowId     = (int) $borrow['id_bor'];
$toolName     = htmlspecialchars($borrow['tool_name_tol']);
$borrowerName = htmlspecialchars($borrow['borrower_name']);
$lenderName   = htmlspecialchars($borrow['lender_name']);
$borrowStatus = htmlspecialchars($borrow['borrow_status']);

$isBorrower = (int) $borrow['borrower_id'] === $authUser['id'];
$otherParty = $isBorrower ? $lenderName : $borrowerName;
$yourRole   = $isBorrower ? 'Borrower' : 'Lender';

$typeLabels = [
    'damage'             => 'Damage',
    'theft'              => 'Theft',
    'loss'               => 'Loss',
    'injury'             => 'Injury',
    'late_return'        => 'Late Return',
    'condition_dispute'  => 'Condition Dispute',
    'other'              => 'Other',
];
?>

<section id="incident-create" aria-labelledby="incident-create-heading">

  <header>
    <h1 id="incident-create-heading">
      <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
      Report an Incident
    </h1>
    <p>File an incident report for your borrow of <strong><?= $toolName ?></strong>.</p>
  </header>

  <?php if ($hasExisting): ?>

    <section aria-label="Existing incident">
      <p role="alert" data-flash="error">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        An open incident report already exists for this borrow transaction.
        Please wait for the current incident to be resolved before filing a new one.
      </p>
      <a href="/dashboard" role="button">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Dashboard
      </a>
    </section>

  <?php else: ?>

    <?php if (!empty($errors)): ?>
      <ul role="alert" aria-label="Form errors">
        <?php foreach ($errors as $msg): ?>
          <li><?= htmlspecialchars($msg) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <dl aria-label="Borrow details">
      <div>
        <dt>Tool</dt>
        <dd><?= $toolName ?></dd>
      </div>
      <div>
        <dt>Your Role</dt>
        <dd><?= $yourRole ?></dd>
      </div>
      <div>
        <dt>Other Party</dt>
        <dd><?= $otherParty ?></dd>
      </div>
      <div>
        <dt>Borrow Status</dt>
        <dd><?= $borrowStatus ?></dd>
      </div>
    </dl>

    <form action="/incidents" method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="borrow_id" value="<?= $borrowId ?>">

      <fieldset>
        <legend>Incident Details</legend>

        <div>
          <label for="incident-type">Incident Type <span aria-hidden="true">*</span></label>
          <select id="incident-type"
                  name="incident_type"
                  required
                  <?php if (isset($errors['incident_type'])): ?>aria-invalid="true" aria-describedby="incident-type-error"<?php endif; ?>>
            <option value="">Select a type&hellip;</option>
            <?php foreach ($incidentTypes as $type): ?>
              <option value="<?= (int) $type['id_ity'] ?>"
                      <?= ((int) ($old['incident_type'] ?? 0)) === (int) $type['id_ity'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($typeLabels[$type['type_name_ity']] ?? $type['type_name_ity']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['incident_type'])): ?>
            <p id="incident-type-error" role="alert"><?= htmlspecialchars($errors['incident_type']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="incident-subject">Subject <span aria-hidden="true">*</span></label>
          <input type="text"
                 id="incident-subject"
                 name="subject"
                 required
                 maxlength="255"
                 autocomplete="off"
                 placeholder="Brief description of the incident"
                 value="<?= htmlspecialchars($old['subject'] ?? '') ?>"
                 <?php if (isset($errors['subject'])): ?>aria-invalid="true" aria-describedby="subject-error"<?php endif; ?>>
          <?php if (isset($errors['subject'])): ?>
            <p id="subject-error" role="alert"><?= htmlspecialchars($errors['subject']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="incident-description">Description <span aria-hidden="true">*</span></label>
          <textarea id="incident-description"
                    name="description"
                    required
                    rows="6"
                    maxlength="5000"
                    placeholder="Explain what happened, the extent of damage or loss, and any relevant details."
                    <?php if (isset($errors['description'])): ?>aria-invalid="true" aria-describedby="description-error"<?php endif; ?>><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
          <?php if (isset($errors['description'])): ?>
            <p id="description-error" role="alert"><?= htmlspecialchars($errors['description']) ?></p>
          <?php endif; ?>
        </div>
      </fieldset>

      <fieldset>
        <legend>When It Happened</legend>

        <div>
          <label for="incident-date">Date of Incident <span aria-hidden="true">*</span></label>
          <input type="date"
                 id="incident-date"
                 name="incident_date"
                 required
                 max="<?= htmlspecialchars(date('Y-m-d')) ?>"
                 value="<?= htmlspecialchars($old['incident_date'] ?? '') ?>"
                 <?php if (isset($errors['incident_date'])): ?>aria-invalid="true" aria-describedby="incident-date-error"<?php endif; ?>>
          <?php if (isset($errors['incident_date'])): ?>
            <p id="incident-date-error" role="alert"><?= htmlspecialchars($errors['incident_date']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="incident-time">Time of Incident <span aria-hidden="true">*</span></label>
          <input type="time"
                 id="incident-time"
                 name="incident_time"
                 required
                 value="<?= htmlspecialchars($old['incident_time'] ?? '') ?>"
                 <?php if (isset($errors['incident_time'])): ?>aria-invalid="true" aria-describedby="incident-time-error"<?php endif; ?>>
          <?php if (isset($errors['incident_time'])): ?>
            <p id="incident-time-error" role="alert"><?= htmlspecialchars($errors['incident_time']) ?></p>
          <?php endif; ?>
        </div>

        <p id="deadline-hint">
          <i class="fa-solid fa-clock" aria-hidden="true"></i>
          Incidents must be reported within 48 hours of occurrence.
        </p>
      </fieldset>

      <fieldset>
        <legend>Estimated Damage</legend>

        <div>
          <label for="incident-amount">Estimated Damage Amount</label>
          <input type="number"
                 id="incident-amount"
                 name="estimated_damage_amount"
                 min="0"
                 max="99999.99"
                 step="0.01"
                 placeholder="0.00"
                 value="<?= htmlspecialchars($old['estimated_damage_amount'] ?? '') ?>"
                 aria-describedby="amount-hint<?= isset($errors['estimated_damage_amount']) ? ' amount-error' : '' ?>"
                 <?php if (isset($errors['estimated_damage_amount'])): ?>aria-invalid="true"<?php endif; ?>>
          <p id="amount-hint">Optional. Enter a dollar estimate of the damage or loss.</p>
          <?php if (isset($errors['estimated_damage_amount'])): ?>
            <p id="amount-error" role="alert"><?= htmlspecialchars($errors['estimated_damage_amount']) ?></p>
          <?php endif; ?>
        </div>
      </fieldset>

      <fieldset>
        <legend>Evidence Photos</legend>

        <div>
          <label for="incident-photos">Upload Photos</label>
          <input type="file"
                 id="incident-photos"
                 name="photos[]"
                 multiple
                 accept="image/jpeg,image/png,image/webp"
                 aria-describedby="photos-hint<?= isset($errors['photos']) ? ' photos-error' : '' ?>"
                 <?php if (isset($errors['photos'])): ?>aria-invalid="true"<?php endif; ?>>
          <p id="photos-hint">Optional. Upload up to 5 photos (JPEG, PNG, or WebP, max 5 MB each).</p>
          <?php if (isset($errors['photos'])): ?>
            <p id="photos-error" role="alert"><?= htmlspecialchars($errors['photos']) ?></p>
          <?php endif; ?>
        </div>
      </fieldset>

      <footer>
        <button type="submit">
          <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit Report
        </button>
        <a href="/dashboard">Cancel</a>
      </footer>
    </form>

  <?php endif; ?>

</section>
