<?php
/**
 * Admin — Global search results grouped by entity type.
 *
 * Variables from AdminController::search():
 *   $term        string  The search query
 *   $results     array   Keyed by entity: users, tools, disputes, events, incidents, neighborhoods
 *   $totalCount  int     Sum of all result counts
 *
 * Return shapes per entity:
 *   users:         id_acc, full_name, email_address_acc, role_name_rol, account_status
 *   tools:         id_tol, tool_name_tol, owner_name, tool_condition, rental_fee_tol
 *   disputes:      id_dsp, reporter_name, borrower_name, lender_name, dispute_status, days_open
 *   events:        id_evt, event_name_evt, start_at_evt, event_address_evt, event_timing
 *   incidents:     id_irt, incident_type, reporter_name, borrower_name, lender_name, incident_status, days_open
 *   neighborhoods: id_nbh, neighborhood_name_nbh, city_name_nbh, state_code_sta, active_members
 *
 * Shared data:
 *   $currentPage  string
 */

$sections = [
    'users' => [
        'icon'  => 'fa-users',
        'label' => 'Users',
        'page'  => '/admin/users',
    ],
    'tools' => [
        'icon'  => 'fa-screwdriver-wrench',
        'label' => 'Tools',
        'page'  => '/admin/tools',
    ],
    'disputes' => [
        'icon'  => 'fa-gavel',
        'label' => 'Disputes',
        'page'  => '/admin/disputes',
    ],
    'events' => [
        'icon'  => 'fa-calendar',
        'label' => 'Events',
        'page'  => '/admin/events',
    ],
    'incidents' => [
        'icon'  => 'fa-flag',
        'label' => 'Incidents',
        'page'  => '/admin/incidents',
    ],
    'neighborhoods' => [
        'icon'  => 'fa-chart-bar',
        'label' => 'Neighborhoods',
        'page'  => '/admin/reports',
    ],
];
?>

<section id="admin-search-page" aria-labelledby="admin-search-heading">

  <header>
    <h1 id="admin-search-heading">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      Search Results
    </h1>
    <?php if ($term !== ''): ?>
      <p>
        <strong><?= number_format($totalCount) ?></strong> result<?= $totalCount !== 1 ? 's' : '' ?>
        for &#8220;<?= htmlspecialchars($term) ?>&#8221;
      </p>
    <?php else: ?>
      <p>Enter a search term to find users, tools, disputes, events, incidents, and neighborhoods.</p>
    <?php endif; ?>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <?php if ($term !== '' && $totalCount === 0): ?>

    <section aria-label="No results">
      <i class="fa-regular fa-face-meh" aria-hidden="true"></i>
      <h2>No Results Found</h2>
      <p>Nothing matched &#8220;<?= htmlspecialchars($term) ?>&#8221; across any category.</p>
      <a href="/admin" role="button">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Dashboard
      </a>
    </section>

  <?php elseif ($term !== ''): ?>

    <?php foreach ($sections as $key => $meta):
      $items = $results[$key];
      $count = count($items);
      if ($count === 0) continue;
    ?>
      <section aria-labelledby="search-<?= $key ?>-heading">
        <h2 id="search-<?= $key ?>-heading">
          <i class="fa-solid <?= $meta['icon'] ?>" aria-hidden="true"></i>
          <?= $meta['label'] ?>
          <span data-badge><?= $count ?></span>
        </h2>

        <div role="list">
          <?php if ($key === 'users'):
            foreach ($items as $user): ?>
              <article role="listitem">
                <h3><a href="/profile/<?= (int) $user['id_acc'] ?>"><?= htmlspecialchars($user['full_name']) ?></a></h3>
                <dl>
                  <div>
                    <dt>Email</dt>
                    <dd><?= htmlspecialchars($user['email_address_acc']) ?></dd>
                  </div>
                  <div>
                    <dt>Role</dt>
                    <dd><span data-role="<?= htmlspecialchars($user['role_name_rol']) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['role_name_rol']))) ?></span></dd>
                  </div>
                  <div>
                    <dt>Status</dt>
                    <dd><span data-status="<?= htmlspecialchars($user['account_status']) ?>"><?= htmlspecialchars(ucfirst($user['account_status'])) ?></span></dd>
                  </div>
                </dl>
              </article>
            <?php endforeach;

          elseif ($key === 'tools'):
            foreach ($items as $tool): ?>
              <article role="listitem">
                <h3><a href="/tools/<?= (int) $tool['id_tol'] ?>"><?= htmlspecialchars($tool['tool_name_tol']) ?></a></h3>
                <dl>
                  <div>
                    <dt>Owner</dt>
                    <dd><?= htmlspecialchars($tool['owner_name']) ?></dd>
                  </div>
                  <div>
                    <dt>Condition</dt>
                    <dd><?= htmlspecialchars(ucfirst($tool['tool_condition'])) ?></dd>
                  </div>
                  <div>
                    <dt>Fee</dt>
                    <dd>$<?= number_format((float) $tool['rental_fee_tol'], 2) ?></dd>
                  </div>
                </dl>
              </article>
            <?php endforeach;

          elseif ($key === 'disputes'):
            foreach ($items as $dispute): ?>
              <article role="listitem">
                <h3><a href="/disputes/<?= (int) $dispute['id_dsp'] ?>">Dispute #<?= (int) $dispute['id_dsp'] ?></a></h3>
                <dl>
                  <div>
                    <dt>Reporter</dt>
                    <dd><?= htmlspecialchars($dispute['reporter_name']) ?></dd>
                  </div>
                  <div>
                    <dt>Borrower</dt>
                    <dd><?= htmlspecialchars($dispute['borrower_name']) ?></dd>
                  </div>
                  <div>
                    <dt>Lender</dt>
                    <dd><?= htmlspecialchars($dispute['lender_name']) ?></dd>
                  </div>
                  <div>
                    <dt>Age</dt>
                    <dd><?= (int) $dispute['days_open'] ?> day<?= (int) $dispute['days_open'] !== 1 ? 's' : '' ?></dd>
                  </div>
                </dl>
              </article>
            <?php endforeach;

          elseif ($key === 'events'):
            foreach ($items as $event): ?>
              <article role="listitem">
                <h3><a href="/events/<?= (int) $event['id_evt'] ?>"><?= htmlspecialchars($event['event_name_evt']) ?></a></h3>
                <dl>
                  <div>
                    <dt>Date</dt>
                    <dd>
                      <time datetime="<?= htmlspecialchars($event['start_at_evt']) ?>">
                        <?= htmlspecialchars(date('M j, Y g:ia', strtotime($event['start_at_evt']))) ?>
                      </time>
                    </dd>
                  </div>
                  <?php if ($event['event_address_evt'] !== null): ?>
                    <div>
                      <dt>Location</dt>
                      <dd><?= htmlspecialchars($event['event_address_evt']) ?></dd>
                    </div>
                  <?php endif; ?>
                  <div>
                    <dt>Timing</dt>
                    <dd><span data-timing><?= htmlspecialchars($event['event_timing']) ?></span></dd>
                  </div>
                </dl>
              </article>
            <?php endforeach;

          elseif ($key === 'incidents'):
            foreach ($items as $incident): ?>
              <article role="listitem">
                <h3><a href="/incidents/<?= (int) $incident['id_irt'] ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $incident['incident_type']))) ?> #<?= (int) $incident['id_irt'] ?></a></h3>
                <dl>
                  <div>
                    <dt>Reporter</dt>
                    <dd><?= htmlspecialchars($incident['reporter_name']) ?></dd>
                  </div>
                  <div>
                    <dt>Borrower</dt>
                    <dd><?= htmlspecialchars($incident['borrower_name']) ?></dd>
                  </div>
                  <div>
                    <dt>Lender</dt>
                    <dd><?= htmlspecialchars($incident['lender_name']) ?></dd>
                  </div>
                  <div>
                    <dt>Age</dt>
                    <dd><?= (int) $incident['days_open'] ?> day<?= (int) $incident['days_open'] !== 1 ? 's' : '' ?></dd>
                  </div>
                </dl>
              </article>
            <?php endforeach;

          elseif ($key === 'neighborhoods'):
            foreach ($items as $neighborhood): ?>
              <article role="listitem">
                <h3><?= htmlspecialchars($neighborhood['neighborhood_name_nbh']) ?></h3>
                <dl>
                  <div>
                    <dt>Location</dt>
                    <dd><?= htmlspecialchars($neighborhood['city_name_nbh']) ?>, <?= htmlspecialchars($neighborhood['state_code_sta']) ?></dd>
                  </div>
                  <div>
                    <dt>Members</dt>
                    <dd><?= number_format((int) $neighborhood['active_members']) ?></dd>
                  </div>
                </dl>
              </article>
            <?php endforeach;

          endif; ?>
        </div>

        <a href="<?= $meta['page'] ?>">
          View all <?= strtolower($meta['label']) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
      </section>
    <?php endforeach; ?>

  <?php endif; ?>

</section>
