<?php $isBookmarked = isset($bookmarkedIds) && in_array((int) $tool['id_tol'], $bookmarkedIds, true); ?>
<?php $isEager = !empty($eagerLoad); $eagerLoad = false; ?>
<?php $headingTag = $cardHeadingLevel ?? 'h2'; ?>
<div role="listitem">
<article
  data-condition="<?= htmlspecialchars($tool['tool_condition'] ?? '') ?>"
  data-owner="<?= htmlspecialchars($tool['owner_name'] ?? '') ?>"
  data-deposit="<?= !empty($tool['is_deposit_required_tol']) ? number_format((float) ($tool['default_deposit_amount_tol'] ?? 0), 2) : '' ?>">
  <figure>
    <?php if (!empty($tool['primary_image'])):
      $variants = \App\Core\ImageProcessor::getAvailableVariants(
          $tool['primary_image'],
          $tool['primary_width'] ?? null,
          \App\Core\ImageProcessor::VARIANT_WIDTHS,
      );
      $srcsets = \App\Core\ImageProcessor::buildSrcset($variants);
      $isWebp = str_ends_with($tool['primary_image'], '.webp');
      $smallestWidth = array_key_first($variants) ?? 400;
      $fallbackFile = $variants[$smallestWidth]['file'] ?? $tool['primary_image'];
      $focalX = (int) ($tool['primary_focal_x'] ?? 50);
      $focalY = (int) ($tool['primary_focal_y'] ?? 50);
      $focalAttrs = ($focalX !== 50 || $focalY !== 50) ? " data-focal-x=\"{$focalX}\" data-focal-y=\"{$focalY}\"" : '';
      $sizes = $cardSizes ?? '(max-width: 400px) calc(50vw - 1.25rem), (max-width: 600px) calc(100vw - 2rem), (max-width: 700px) calc((100vw - 4rem) / 3), 270px';
    ?>
      <picture>
        <?php if (!$isWebp && $srcsets['webpSrcset'] !== ''): ?>
          <source type="image/webp"
                  srcset="<?= $srcsets['webpSrcset'] ?>"
                  sizes="<?= $sizes ?>">
        <?php endif; ?>
        <img src="<?= htmlspecialchars(\App\Core\ViewHelper::uploadVersion('/uploads/tools/' . $fallbackFile)) ?>"
             srcset="<?= $srcsets['srcset'] ?>"
             sizes="<?= $sizes ?>"
             alt="<?= htmlspecialchars($tool['tool_name_tol']) ?>"
             width="<?= $smallestWidth ?>" height="<?= (int) round($smallestWidth * 0.75) ?>"
             <?= $isEager ? 'fetchpriority="high" decoding="sync"' : 'loading="lazy" decoding="async"' ?><?= $focalAttrs ?>>
      </picture>
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
              data-intent="ghost" data-shape="icon"
              aria-label="<?= $isBookmarked ? 'Remove bookmark for' : 'Bookmark' ?> <?= htmlspecialchars($tool['tool_name_tol']) ?>">
        <i class="fa-<?= $isBookmarked ? 'solid' : 'regular' ?> fa-bookmark" aria-hidden="true"></i>
      </button>
    </form>
  <?php elseif (!empty($isLoggedIn) && ($authUser['id'] ?? 0) === (int) ($tool['owner_id'] ?? 0)): ?>
    <span data-badge="owner"><i class="fa-solid fa-user" aria-hidden="true"></i> YOUR TOOL</span>
  <?php endif; ?>
  <?php if (!empty($tool['is_lent_out'])): ?>
    <span data-badge="lent">LENT OUT</span>
  <?php elseif (!empty($tool['is_new_arrival'])): ?>
    <span data-badge="new">JUST LISTED</span>
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
      <?php $ratingCount = (int) ($tool['rating_count'] ?? 0); ?>
      <?php if ($ratingCount > 0): ?>
        <?php $avg = (int) round($tool['avg_rating'] ?? 0); ?>
        <span role="img" aria-label="<?= htmlspecialchars((string) $avg) ?> out of 5 stars">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fa-<?= $i <= $avg ? 'solid' : 'regular' ?> fa-star" aria-hidden="true"></i>
          <?php endfor; ?>
        </span>
      <?php else: ?>
        <span>No ratings yet</span>
      <?php endif; ?>
      <?php if (isset($tool['distance_miles'])): ?>
        <span role="img" aria-label="<?= htmlspecialchars($tool['distance_miles']) ?> miles away">
          <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
          <?= htmlspecialchars($tool['distance_miles']) ?> mi
        </span>
      <?php endif; ?>
      <img src="<?= htmlspecialchars(\App\Core\ViewHelper::avatarUrl($tool['owner_vector_avatar'] ?? null, $tool['owner_avatar'] ?? null)) ?>"
           alt="<?= htmlspecialchars($tool['owner_name'] ?? 'Owner') ?>"
           width="28" height="28"
           loading="lazy"
           decoding="async">
    </footer>
  </div>
</article>
</div>
