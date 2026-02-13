<?php $isBookmarked = isset($bookmarkedIds) && in_array((int) $tool['id_tol'], $bookmarkedIds, true); ?>
<article role="listitem">
  <figure>
    <?php if (!empty($tool['primary_image'])): ?>
      <img src="/uploads/tools/<?= htmlspecialchars($tool['primary_image']) ?>"
           alt="<?= htmlspecialchars($tool['tool_name_tol']) ?>"
           width="220" height="180"
           loading="lazy"
           decoding="async">
    <?php else: ?>
      <img src="/assets/images/tool-placeholder.png"
           alt="<?= htmlspecialchars($tool['tool_name_tol']) ?>"
           width="220" height="180"
           loading="lazy"
           decoding="async">
    <?php endif; ?>
    <form method="post" action="/tools/<?= (int) $tool['id_tol'] ?>/bookmark">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <button type="submit"
              aria-label="<?= $isBookmarked ? 'Remove bookmark for' : 'Bookmark' ?> <?= htmlspecialchars($tool['tool_name_tol']) ?>">
        <i class="fa-<?= $isBookmarked ? 'solid' : 'regular' ?> fa-bookmark" aria-hidden="true"></i>
      </button>
    </form>
  </figure>
  <div>
    <?php if (!empty($tool['category_name'])): ?>
      <span><?= htmlspecialchars($tool['category_name']) ?></span>
    <?php endif; ?>
    <h3><a href="/tools/<?= (int) $tool['id_tol'] ?>"><?= htmlspecialchars($tool['tool_name_tol']) ?></a></h3>
    <p>$<?= number_format((float) ($tool['rental_fee_tol'] ?? 0), 2) ?><span>/day</span></p>
    <footer>
      <?php $avg = (int) round($tool['avg_rating'] ?? 0); ?>
      <span role="img" aria-label="<?= $avg ?> out of 5 stars">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="fa-<?= $i <= $avg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
        <?php endfor; ?>
      </span>
      <img src="<?= htmlspecialchars(($tool['owner_avatar'] ?? null) ? '/uploads/profiles/' . $tool['owner_avatar'] : '/assets/images/avatar-placeholder.svg') ?>"
           alt="<?= htmlspecialchars($tool['owner_name'] ?? 'Owner') ?>"
           width="28" height="28"
           loading="lazy"
           decoding="async">
    </footer>
  </div>
</article>
