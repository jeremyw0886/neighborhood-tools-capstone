<?php
/**
 * Configurable sort/filter form for dashboard tables and card lists.
 *
 * @var string      $paramPrefix    Query param prefix (e.g. 'req_', 'lent_', 'borrow_')
 * @var array       $sortOptions    Associative array value => label
 * @var string      $currentSort    Currently selected sort column
 * @var string      $currentDir     Currently selected direction ('asc' | 'desc')
 * @var array|null  $filterOptions  Optional associative array value => label for status filter
 * @var string|null $currentFilter  Currently selected filter value
 * @var string|null $filterLabel    Label for the filter select (defaults to 'Status')
 * @var array|null  $preserveParams Optional hidden inputs to preserve cross-form state
 */

use App\Core\ViewHelper;

$p = htmlspecialchars($paramPrefix);
?>
<form method="get" action="" data-sort-form aria-label="Sort<?= !empty($filterOptions) ? ' and filter' : '' ?>">
  <fieldset>
    <legend class="visually-hidden">Sort<?= !empty($filterOptions) ? ' and filter' : '' ?> options</legend>
    <?php foreach ($preserveParams ?? [] as $name => $value): ?>
      <?php if ($value !== '' && $value !== null): ?>
        <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value) ?>">
      <?php endif; ?>
    <?php endforeach; ?>

    <label>
      Sort by
      <select name="<?= $p ?>sort">
        <?php foreach ($sortOptions as $value => $label): ?>
          <option value="<?= htmlspecialchars($value) ?>"<?= ViewHelper::selected($currentSort, $value) ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      Direction
      <select name="<?= $p ?>dir">
        <option value="asc"<?= ViewHelper::selected($currentDir, 'asc') ?>>Ascending</option>
        <option value="desc"<?= ViewHelper::selected($currentDir, 'desc') ?>>Descending</option>
      </select>
    </label>

    <?php if (!empty($filterOptions)): ?>
      <label>
        <?= htmlspecialchars($filterLabel ?? 'Status') ?>
        <select name="<?= $p ?>status">
          <?php foreach ($filterOptions as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"<?= ViewHelper::selected($currentFilter ?? '', $value) ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>

    <button type="submit" data-intent="ghost" data-size="sm">Sort</button>
  </fieldset>
</form>
