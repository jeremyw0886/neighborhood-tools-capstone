<?php
/**
 * Admin search bar — global search across all admin entities.
 *
 * Included in admin-nav.php after the closing </nav> tag.
 */
?>
<search aria-label="Search all admin data">
  <form method="get" action="/admin/search" role="search">
    <label for="admin-search-input" class="visually-hidden">Search all admin data</label>
    <span aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
    <input id="admin-search-input"
           type="search"
           name="q"
           placeholder="Search users, tools, disputes&#8230;"
           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
           autocomplete="off"
           data-suggest="admin" data-suggest-type="all">
    <button type="submit" data-intent="primary" data-shape="pill"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Search</button>
  </form>
</search>
