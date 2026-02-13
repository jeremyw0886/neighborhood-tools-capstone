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
      <button type="submit" aria-label="Bookmark <?= htmlspecialchars($tool['tool_name_tol']) ?>">
        <i class="fa-regular fa-bookmark" aria-hidden="true"></i>
      </button>
    </form>
  </figure>
  <div>
    <h3><a href="/tools/<?= (int) $tool['id_tol'] ?>"><?= htmlspecialchars($tool['tool_name_tol']) ?></a></h3>
    <footer>
      <p>
        <?php $avg = round($tool['avg_rating'] ?? 0); ?>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="fa-<?= $i <= $avg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
        <?php endfor; ?>
        <span class="visually-hidden"><?= $avg ?> out of 5 stars</span>
      </p>
      <img src="<?= htmlspecialchars(($tool['owner_avatar'] ?? null) ? '/uploads/profiles/' . $tool['owner_avatar'] : '/assets/images/avatar-placeholder.png') ?>"
           alt="<?= htmlspecialchars($tool['owner_name'] ?? 'Owner') ?>"
           width="28" height="28"
           loading="lazy"
           decoding="async">
    </footer>
  </div>
</article>
