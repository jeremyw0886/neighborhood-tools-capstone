<?php
/**
 * Admin sub-navigation — shared across all admin pages.
 *
 * Uses $currentPage (from getSharedData()) to set aria-current="page"
 * on the active link. Included via require in each admin view.
 */
?>
<nav aria-label="Admin navigation">
  <ul>
    <li><a href="/admin"<?= $currentPage === '/admin' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Dashboard</a></li>
    <li><a href="/admin/users"<?= $currentPage === '/admin/users' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-users" aria-hidden="true"></i> Users</a></li>
    <li><a href="/admin/tools"<?= $currentPage === '/admin/tools' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Tools</a></li>
    <li><a href="/admin/disputes"<?= $currentPage === '/admin/disputes' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-gavel" aria-hidden="true"></i> Disputes</a></li>
    <li><a href="/admin/events"<?= $currentPage === '/admin/events' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-calendar" aria-hidden="true"></i> Events</a></li>
    <li><a href="/admin/incidents"<?= $currentPage === '/admin/incidents' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-flag" aria-hidden="true"></i> Incidents</a></li>
    <li><a href="/admin/categories"<?= $currentPage === '/admin/categories' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-tags" aria-hidden="true"></i> Categories</a></li>
    <li><a href="/admin/reports"<?= $currentPage === '/admin/reports' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-chart-bar" aria-hidden="true"></i> Reports</a></li>
    <li><a href="/admin/audit-log"<?= $currentPage === '/admin/audit-log' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Audit Log</a></li>
    <li><a href="/admin/tos"<?= $currentPage === '/admin/tos' ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-file-contract" aria-hidden="true"></i> TOS</a></li>
  </ul>
</nav>
