<?php
/**
 * Admin dashboard — platform-wide summary stats and quick links.
 *
 * Variables from AdminController::dashboard():
 *   $stats   array{totalMembers, activeMembers, availableTools,
 *                  openDisputes, pendingDeposits, openIncidents, upcomingEvents}
 *   $trends  array  Rows from PlatformStats::getRecentTrends() (14 days, newest first)
 *
 * Each trend row contains:
 *   stat_date, total_accounts, active_accounts, new_accounts_today,
 *   total_tools, available_tools, new_tools_today,
 *   active_borrows, completed_today, new_requests_today,
 *   open_disputes, open_incidents, overdue_borrows, deposits_held_total
 *
 * Shared data:
 *   $authUser     array{id, name, first_name, role, avatar}
 *   $currentPage  string
 */
?>

<section aria-labelledby="admin-heading">

  <header>
    <h1 id="admin-heading">
      <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
      Admin Dashboard
    </h1>
    <p>Platform overview and management tools.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <section aria-labelledby="admin-summary-heading">
    <h2 id="admin-summary-heading" class="visually-hidden">Platform Summary</h2>

    <div role="list">

      <article role="listitem">
        <a href="/admin/users">
          <i class="fa-solid fa-users" aria-hidden="true"></i>
          <h3>Total Members</h3>
          <p><?= $stats['totalMembers'] ?></p>
          <span><?= $stats['activeMembers'] ?> active</span>
        </a>
      </article>

      <article role="listitem">
        <a href="/admin/tools">
          <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
          <h3>Available Tools</h3>
          <p><?= $stats['availableTools'] ?></p>
          <span>View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
      </article>

      <?php if ($stats['openDisputes'] > 0): ?>
        <article role="listitem" data-urgent>
          <a href="/admin/disputes">
            <i class="fa-solid fa-gavel" aria-hidden="true"></i>
            <h3>Open Disputes</h3>
            <p><?= $stats['openDisputes'] ?></p>
            <span>Needs attention <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
          </a>
        </article>
      <?php else: ?>
        <article role="listitem">
          <a href="/admin/disputes">
            <i class="fa-solid fa-gavel" aria-hidden="true"></i>
            <h3>Open Disputes</h3>
            <p>0</p>
            <span>All clear</span>
          </a>
        </article>
      <?php endif; ?>

      <article role="listitem"<?= $stats['pendingDeposits'] > 0 ? ' data-warning' : '' ?>>
        <a href="/admin/reports">
          <i class="fa-solid fa-vault" aria-hidden="true"></i>
          <h3>Pending Deposits</h3>
          <p><?= $stats['pendingDeposits'] ?></p>
          <span>View details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
      </article>

      <?php if ($stats['openIncidents'] > 0): ?>
        <article role="listitem" data-urgent>
          <a href="/admin/incidents">
            <i class="fa-solid fa-flag" aria-hidden="true"></i>
            <h3>Open Incidents</h3>
            <p><?= $stats['openIncidents'] ?></p>
            <span>Needs attention <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
          </a>
        </article>
      <?php else: ?>
        <article role="listitem">
          <a href="/admin/incidents">
            <i class="fa-solid fa-flag" aria-hidden="true"></i>
            <h3>Open Incidents</h3>
            <p>0</p>
            <span>All clear</span>
          </a>
        </article>
      <?php endif; ?>

      <article role="listitem">
        <a href="/admin/events">
          <i class="fa-solid fa-calendar" aria-hidden="true"></i>
          <h3>Upcoming Events</h3>
          <p><?= $stats['upcomingEvents'] ?></p>
          <span>View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
      </article>

    </div>
  </section>

  <?php if (!empty($trends)): ?>
    <section aria-labelledby="admin-trends-heading">
      <h2 id="admin-trends-heading">
        <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
        14-Day Trends
      </h2>

      <div role="region" aria-labelledby="admin-trends-heading" tabindex="0">
        <table>
          <thead>
            <tr>
              <th scope="col">Date</th>
              <th scope="col">Members</th>
              <th scope="col">New</th>
              <th scope="col">Tools</th>
              <th scope="col">Borrows</th>
              <th scope="col">Completed</th>
              <th scope="col">Requests</th>
              <th scope="col">Disputes</th>
              <th scope="col">Incidents</th>
              <th scope="col">Overdue</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trends as $day):
              $overdue  = (int) $day['overdue_borrows'];
              $disputes = (int) $day['open_disputes'];
            ?>
              <tr>
                <td>
                  <time datetime="<?= htmlspecialchars($day['stat_date']) ?>">
                    <?= htmlspecialchars(date('M j', strtotime($day['stat_date']))) ?>
                  </time>
                </td>
                <td>
                  <span><?= number_format((int) $day['active_accounts']) ?></span>
                  <small>of <?= number_format((int) $day['total_accounts']) ?></small>
                </td>
                <td><?= number_format((int) $day['new_accounts_today']) ?></td>
                <td>
                  <span><?= number_format((int) $day['available_tools']) ?></span>
                  <small>of <?= number_format((int) $day['total_tools']) ?></small>
                </td>
                <td><?= number_format((int) $day['active_borrows']) ?></td>
                <td><?= number_format((int) $day['completed_today']) ?></td>
                <td><?= number_format((int) $day['new_requests_today']) ?></td>
                <td<?= $disputes > 0 ? ' data-warning' : '' ?>>
                  <?= $disputes ?>
                </td>
                <td<?= (int) $day['open_incidents'] > 0 ? ' data-warning' : '' ?>>
                  <?= (int) $day['open_incidents'] ?>
                </td>
                <td<?= $overdue > 0 ? ' data-urgent' : '' ?>>
                  <?= $overdue ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <section aria-labelledby="admin-actions-heading">
    <h2 id="admin-actions-heading">Quick Actions</h2>
    <ul>
      <li>
        <a href="/admin/users">
          <i class="fa-solid fa-users" aria-hidden="true"></i> Manage Users
        </a>
      </li>
      <li>
        <a href="/admin/disputes">
          <i class="fa-solid fa-gavel" aria-hidden="true"></i> Review Disputes
        </a>
      </li>
      <li>
        <a href="/admin/incidents">
          <i class="fa-solid fa-flag" aria-hidden="true"></i> Review Incidents
        </a>
      </li>
      <li>
        <a href="/admin/tos">
          <i class="fa-solid fa-file-contract" aria-hidden="true"></i> Manage TOS
        </a>
      </li>
      <li>
        <a href="/dashboard">
          <i class="fa-solid fa-gauge" aria-hidden="true"></i> My Dashboard
        </a>
      </li>
    </ul>
  </section>

</section>
