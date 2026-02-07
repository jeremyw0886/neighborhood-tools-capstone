<article class="tool-card" role="listitem">
  <figure class="tool-card-image">
    <?php if (!empty($tool['primary_image'])): ?>
      <img src="/uploads/tools/<?= htmlspecialchars($tool['primary_image']) ?>"
           alt="<?= htmlspecialchars($tool['tool_name_tol']) ?>"
           loading="lazy">
    <?php else: ?>
      <img src="/assets/images/tool-placeholder.png"
           alt="<?= htmlspecialchars($tool['tool_name_tol']) ?>"
           loading="lazy">
    <?php endif; ?>
    <button class="tool-bookmark" type="button" aria-label="Bookmark <?= htmlspecialchars($tool['tool_name_tol']) ?>">
      <i class="fa-regular fa-bookmark" aria-hidden="true"></i>
    </button>
  </figure>
  <div class="tool-card-info">
    <h3><a href="/tools/<?= $tool['id_tol'] ?>"><?= htmlspecialchars($tool['tool_name_tol']) ?></a></h3>
    <footer class="tool-card-footer">
      <p class="tool-rating">
        <?php $avg = round($tool['avg_rating'] ?? 0); ?>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="fa-<?= $i <= $avg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
        <?php endfor; ?>
        <span class="visually-hidden"><?= $avg ?> out of 5 stars</span>
      </p>
      <img src="<?= htmlspecialchars($tool['owner_avatar'] ? '/uploads/profiles/' . $tool['owner_avatar'] : '/assets/images/avatar-placeholder.png') ?>"
           alt="<?= htmlspecialchars($tool['owner_name'] ?? 'Owner') ?>"
           class="tool-owner-avatar"
           loading="lazy">
    </footer>
  </div>
</article>
