<?php
/**
 * Availability management — view and manage date blocks for a tool.
 *
 * Variables from ToolController::availability():
 *
 * @var array  $tool    Tool row from Tool::findById()
 * @var array  $blocks  Rows from AvailabilityBlock::getForTool()
 * @var array  $errors  Flash validation errors (field-keyed)
 * @var array  $old     Flash sticky input values
 * @var string $success Flash success message
 *
 * Shared data:
 *
 * @var string $csrfToken
 */

$blocks  ??= [];
$errors  ??= [];
$old     ??= [];
$success ??= '';
$toolId  = (int) $tool['id_tol'];
?>

<section aria-labelledby="availability-heading">

  <nav aria-label="Back">
    <a href="<?= htmlspecialchars($backUrl) ?>">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
    </a>
  </nav>

  <header>
    <h1 id="availability-heading">
      <i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i> Manage Availability
    </h1>
    <p>Set date ranges when <strong><?= htmlspecialchars($tool['tool_name_tol']) ?></strong> is unavailable.</p>
  </header>

  <?php if ($success !== ''): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($success) ?></p>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <ul role="alert" aria-label="Form errors">
      <?php foreach ($errors as $msg): ?>
        <li><?= htmlspecialchars($msg) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php $isListed = (bool) $tool['is_available_tol']; ?>
  <section aria-labelledby="listing-status-heading">
    <h2 id="listing-status-heading">
      <?php if ($isListed): ?>
        <i class="fa-solid fa-eye" aria-hidden="true"></i> Listed
      <?php else: ?>
        <i class="fa-solid fa-eye-slash" aria-hidden="true"></i> Unlisted
      <?php endif; ?>
    </h2>
    <p>
      <?php if ($isListed): ?>
        This tool appears in browse and search results. Unlisting hides it from other members without deleting it.
      <?php else: ?>
        This tool is hidden from browse and search results. Re-list it to make it visible again.
      <?php endif; ?>
    </p>
    <form method="post" action="/tools/<?= $toolId ?>/toggle-listing">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <?php if ($isListed): ?>
        <button type="submit" data-action="unlist" data-intent="danger">
          <i class="fa-solid fa-eye-slash" aria-hidden="true"></i> Unlist Tool
        </button>
      <?php else: ?>
        <button type="submit" data-action="relist" data-intent="success">
          <i class="fa-solid fa-eye" aria-hidden="true"></i> Re-list Tool
        </button>
      <?php endif; ?>
    </form>
  </section>

  <?php if ($blocks !== []): ?>
    <section aria-labelledby="blocks-heading">
      <h2 id="blocks-heading">Current Blocks</h2>
      <div>
        <table>
          <thead>
            <tr>
              <th scope="col">Type</th>
              <th scope="col">Start</th>
              <th scope="col">End</th>
              <th scope="col">Notes</th>
              <th scope="col"><span class="visually-hidden">Actions</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($blocks as $block):
              $start    = strtotime($block['start_at_avb']);
              $end      = strtotime($block['end_at_avb']);
              $now      = time();
              $isActive = $start <= $now && $end >= $now;
              $isPast   = $end < $now;
              $isAdmin  = $block['block_type'] === 'admin';
            ?>
              <tr<?php if ($isActive): ?> data-status="active"<?php elseif ($isPast): ?> data-status="past"<?php endif; ?>>
                <td>
                  <?php if ($isAdmin): ?>
                    <i class="fa-solid fa-lock" aria-hidden="true"></i> Admin
                  <?php else: ?>
                    <i class="fa-solid fa-handshake" aria-hidden="true"></i>
                    Borrow<?php if (!empty($block['borrower_name'])): ?> &mdash; <?= htmlspecialchars($block['borrower_name']) ?><?php endif; ?>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars(date('M j, Y', $start)) ?></td>
                <td><?= htmlspecialchars(date('M j, Y', $end)) ?></td>
                <td><?= $block['notes_text_avb'] !== null ? htmlspecialchars($block['notes_text_avb']) : '' ?></td>
                <td>
                  <?php if ($isAdmin): ?>
                    <form method="post" action="/tools/<?= $toolId ?>/availability/delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <input type="hidden" name="block_id" value="<?= (int) $block['id_avb'] ?>">
                      <button type="submit" data-intent="danger" data-size="sm" aria-label="Remove block starting <?= htmlspecialchars(date('M j, Y', $start)) ?>">
                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Remove
                      </button>
                    </form>
                  <?php else: ?>
                    <span><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Auto</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php else: ?>
    <p>No availability blocks set. This tool is available whenever it is not borrowed.</p>
  <?php endif; ?>

  <section aria-labelledby="add-block-heading">
    <h2 id="add-block-heading">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> Add Unavailability Block
    </h2>

    <form method="post" action="/tools/<?= $toolId ?>/availability" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

      <fieldset>
        <legend>Block Details</legend>

        <div>
          <label for="block-start">Start Date <span aria-hidden="true">*</span></label>
          <input type="date"
                 id="block-start"
                 name="start_at"
                 required
                 min="<?= date('Y-m-d') ?>"
                 value="<?= htmlspecialchars($old['start_at'] ?? '') ?>"
                 <?php if (isset($errors['start_at'])): ?>aria-invalid="true" aria-describedby="start-error"<?php endif; ?>>
          <?php if (isset($errors['start_at'])): ?>
            <p id="start-error" role="alert"><?= htmlspecialchars($errors['start_at']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="block-end">End Date <span aria-hidden="true">*</span></label>
          <input type="date"
                 id="block-end"
                 name="end_at"
                 required
                 min="<?= date('Y-m-d') ?>"
                 value="<?= htmlspecialchars($old['end_at'] ?? '') ?>"
                 <?php if (isset($errors['end_at'])): ?>aria-invalid="true" aria-describedby="end-error"<?php endif; ?>>
          <?php if (isset($errors['end_at'])): ?>
            <p id="end-error" role="alert"><?= htmlspecialchars($errors['end_at']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="block-notes">Notes</label>
          <textarea id="block-notes"
                    name="notes"
                    rows="3"
                    maxlength="500"
                    placeholder="e.g. On vacation, tool in maintenance&hellip;"><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <button type="submit" data-intent="primary">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Add Block
      </button>
    </form>
  </section>

</section>
