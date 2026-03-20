<?php
/**
 * Tool search bar — pill-shaped input that submits to /tools.
 *
 * Included on dashboard pages for quick tool search access.
 */
?>
<form role="search" action="/tools" method="get" aria-label="Search tools">
  <fieldset aria-label="Search">
    <label for="dashboard-tool-search" class="visually-hidden">Search tools</label>
    <span aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
    <input type="search"
           id="dashboard-tool-search"
           name="q"
           placeholder="Search tools by name or description…"
           autocomplete="off"
           data-suggest="tools">
    <button type="submit" data-intent="primary" data-shape="pill">
      <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
      <span>Search</span>
    </button>
  </fieldset>
</form>
