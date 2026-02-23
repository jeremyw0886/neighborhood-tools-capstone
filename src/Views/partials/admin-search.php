<?php
/**
 * Admin search bar — global search across all admin entities.
 *
 * Included in admin-nav.php after the closing </nav> tag.
 * The <search> landmark wraps the form; JS enhances with a
 * live-results dropdown that populates the listbox container.
 */
?>
<search aria-label="Search all admin data">
  <form method="get" action="/admin/search" role="search">
    <label for="admin-search-input" class="visually-hidden">Search all admin data</label>
    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
    <input id="admin-search-input"
           type="search"
           name="q"
           placeholder="Search users, tools, disputes&#8230;"
           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
           autocomplete="off"
           aria-expanded="false"
           aria-controls="admin-search-results"
           aria-autocomplete="list">
    <button type="button" aria-label="Clear search" hidden>
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
    <ul id="admin-search-results" role="listbox" aria-label="Search suggestions" hidden></ul>
  </form>
</search>
