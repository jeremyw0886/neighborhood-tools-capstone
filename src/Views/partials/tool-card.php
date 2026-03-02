<?php $isBookmarked = isset($bookmarkedIds) && in_array((int) $tool['id_tol'], $bookmarkedIds, true); ?>
<?php $isEager = !empty($eagerLoad); $eagerLoad = false; ?>
<?php $headingTag = $cardHeadingLevel ?? 'h2'; ?>
<article role="listitem">
  <figure>
    <?php if (!empty($tool['primary_image'])):
      $imgFile = htmlspecialchars($tool['primary_image']);
      $imgSmall = htmlspecialchars(preg_replace('/\.(\w+)$/', '-400w.$1', $tool['primary_image']));
    ?>
      <img src="/uploads/tools/<?= $imgSmall ?>"
           srcset="/uploads/tools/<?= $imgSmall ?> 400w, /uploads/tools/<?= $imgFile ?> 800w"
           sizes="(max-width: 640px) 47vw, 220px"
           alt="<?= htmlspecialchars($tool['tool_name_tol']) ?>"
           width="400" height="268"
           <?= $isEager ? 'fetchpriority="high" decoding="sync"' : 'loading="lazy" decoding="async"' ?>>
    <?php else: ?>
      <img src="/assets/images/tool-placeholder.svg"
           alt="<?= htmlspecialchars($tool['tool_name_tol']) ?>"
           width="220" height="180"
           <?= $isEager ? 'fetchpriority="high" decoding="sync"' : 'loading="lazy" decoding="async"' ?>>
    <?php endif; ?>
  </figure>
  <?php if (!empty($isLoggedIn) && ($authUser['id'] ?? 0) !== (int) ($tool['owner_id'] ?? 0)): ?>
    <form method="post" action="/tools/<?= (int) $tool['id_tol'] ?>/bookmark">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <button type="submit"
              aria-label="<?= $isBookmarked ? 'Remove bookmark for' : 'Bookmark' ?> <?= htmlspecialchars($tool['tool_name_tol']) ?>">
        <i class="fa-<?= $isBookmarked ? 'solid' : 'regular' ?> fa-bookmark" aria-hidden="true"></i>
      </button>
    </form>
  <?php endif; ?>
  <?php if (!empty($tool['is_lent_out'])): ?>
    <span aria-label="Currently lent out">LENT OUT</span>
  <?php elseif (!empty($tool['is_new_arrival'])): ?>
    <span aria-label="New arrival">NEW</span>
  <?php endif; ?>
  <div>
    <?php if (!empty($tool['category_name'])): ?>
      <span>
        <?php if (!empty($tool['category_icon'])): ?>
          <img src="/uploads/vectors/<?= htmlspecialchars($tool['category_icon']) ?>"
               alt="" width="16" height="16" loading="lazy" decoding="async">
        <?php endif; ?>
        <?= htmlspecialchars($tool['category_name']) ?>
      </span>
    <?php endif; ?>
    <<?= $headingTag ?>><a href="/tools/<?= (int) $tool['id_tol'] ?>"><?= htmlspecialchars($tool['tool_name_tol']) ?></a></<?= $headingTag ?>>
    <p>$<?= number_format((float) ($tool['rental_fee_tol'] ?? 0), 2) ?><span>/day</span></p>
    <footer>
      <?php $avg = (int) round($tool['avg_rating'] ?? 0); ?>
      <span role="img" aria-label="<?= htmlspecialchars((string) $avg) ?> out of 5 stars">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="fa-<?= $i <= $avg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
        <?php endfor; ?>
      </span>
      <?php if (isset($tool['distance_miles'])): ?>
        <span aria-label="<?= htmlspecialchars($tool['distance_miles']) ?> miles away">
          <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
          <?= htmlspecialchars($tool['distance_miles']) ?> mi
        </span>
      <?php endif; ?>
      <?php
        if (!empty($tool['owner_vector_avatar'])) {
            $ownerAvatarSrc = '/uploads/vectors/' . $tool['owner_vector_avatar'];
        } elseif (!empty($tool['owner_avatar'])) {
            $ownerAvatarSrc = '/uploads/profiles/' . $tool['owner_avatar'];
        } else {
            $ownerAvatarSrc = '/assets/images/avatar-placeholder.svg';
        }
      ?>
      <img src="<?= htmlspecialchars($ownerAvatarSrc) ?>"
           alt="<?= htmlspecialchars($tool['owner_name'] ?? 'Owner') ?>"
           width="28" height="28"
           loading="lazy"
           decoding="async">
    </footer>
  </div>
</article>
