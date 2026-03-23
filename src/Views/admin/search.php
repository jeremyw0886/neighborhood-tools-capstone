<?php
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
    'categories' => [
        'icon'  => 'fa-tags',
        'label' => 'Categories',
        'page'  => '/admin/categories',
    ],
    'icons' => [
        'icon'  => 'fa-icons',
        'label' => 'Category Icons',
        'page'  => '/admin/images',
    ],
    'avatars' => [
        'icon'  => 'fa-circle-user',
        'label' => 'Profile Avatars',
        'page'  => '/admin/images',
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
    'deposits' => [
        'icon'  => 'fa-vault',
        'label' => 'Deposits',
        'page'  => '/admin/deposits',
    ],
    'neighborhoods' => [
        'icon'  => 'fa-chart-bar',
        'label' => 'Neighborhoods',
        'page'  => '/admin/reports',
    ],
];
?>

  <?php if ($term !== '' && $totalCount === 0): ?>

    <section aria-label="No results">
      <i class="fa-regular fa-face-meh" aria-hidden="true"></i>
      <h2>No Results Found</h2>
      <p>Nothing matched &#8220;<?= htmlspecialchars($term) ?>&#8221; across any category.</p>
      <a href="<?= htmlspecialchars($backUrl) ?>" role="button" data-intent="secondary">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
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
              <article>
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
              <article>
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

          elseif ($key === 'categories'):
            foreach ($items as $cat): ?>
              <article>
                <h3><a href="/admin/categories"><?= htmlspecialchars($cat['category_name_cat']) ?></a></h3>
                <dl>
                  <div>
                    <dt>Total Tools</dt>
                    <dd><?= number_format((int) $cat['total_tools']) ?></dd>
                  </div>
                  <div>
                    <dt>Available</dt>
                    <dd><?= number_format((int) $cat['available_tools']) ?></dd>
                  </div>
                </dl>
              </article>
            <?php endforeach;

          elseif ($key === 'icons'):
            foreach ($items as $icon): ?>
              <article>
                <h3><a href="/admin/images"><?= htmlspecialchars($icon['file_name_vec']) ?></a></h3>
                <dl>
                  <?php if ($icon['description_text_vec'] !== null): ?>
                    <div>
                      <dt>Description</dt>
                      <dd><?= htmlspecialchars($icon['description_text_vec']) ?></dd>
                    </div>
                  <?php endif; ?>
                  <div>
                    <dt>Category</dt>
                    <dd><?= $icon['assigned_category'] !== null ? htmlspecialchars($icon['assigned_category']) : 'Unassigned' ?></dd>
                  </div>
                </dl>
              </article>
            <?php endforeach;

          elseif ($key === 'avatars'):
            foreach ($items as $avatar): ?>
              <article>
                <h3><a href="/admin/images"><?= htmlspecialchars($avatar['file_name_avv']) ?></a></h3>
                <dl>
                  <?php if ($avatar['description_text_avv'] !== null): ?>
                    <div>
                      <dt>Description</dt>
                      <dd><?= htmlspecialchars($avatar['description_text_avv']) ?></dd>
                    </div>
                  <?php endif; ?>
                  <div>
                    <dt>Status</dt>
                    <dd><span data-status="<?= (int) $avatar['is_active_avv'] ? 'active' : 'inactive' ?>"><?= (int) $avatar['is_active_avv'] ? 'Active' : 'Inactive' ?></span></dd>
                  </div>
                </dl>
              </article>
            <?php endforeach;

          elseif ($key === 'disputes'):
            foreach ($items as $dispute): ?>
              <article>
                <h3><a href="/disputes/<?= (int) $dispute['id_dsp'] ?>">Dispute #<?= (int) $dispute['id_dsp'] ?></a></h3>
                <dl>
                  <div>
                    <dt>Tool</dt>
                    <dd><?= htmlspecialchars($dispute['tool_name_tol']) ?></dd>
                  </div>
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
              <article>
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
              <article>
                <h3><a href="/incidents/<?= (int) $incident['id_irt'] ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $incident['incident_type']))) ?> #<?= (int) $incident['id_irt'] ?></a></h3>
                <dl>
                  <div>
                    <dt>Tool</dt>
                    <dd><?= htmlspecialchars($incident['tool_name_tol']) ?></dd>
                  </div>
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

          elseif ($key === 'deposits'):
            foreach ($items as $deposit): ?>
              <article>
                <h3><a href="/payments/deposit/<?= (int) $deposit['id_sdp'] ?>">Deposit #<?= (int) $deposit['id_sdp'] ?></a></h3>
                <dl>
                  <div>
                    <dt>Amount</dt>
                    <dd>$<?= number_format((float) $deposit['amount_sdp'], 2) ?></dd>
                  </div>
                  <div>
                    <dt>Status</dt>
                    <dd><span data-deposit-status="<?= htmlspecialchars($deposit['deposit_status']) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $deposit['deposit_status']))) ?></span></dd>
                  </div>
                  <div>
                    <dt>Tool</dt>
                    <dd><?= htmlspecialchars($deposit['tool_name_tol']) ?></dd>
                  </div>
                  <div>
                    <dt>Borrower</dt>
                    <dd><?= htmlspecialchars($deposit['borrower_name']) ?></dd>
                  </div>
                  <div>
                    <dt>Lender</dt>
                    <dd><?= htmlspecialchars($deposit['lender_name']) ?></dd>
                  </div>
                  <div>
                    <dt>Action</dt>
                    <dd><?= htmlspecialchars($deposit['action_required']) ?></dd>
                  </div>
                </dl>
              </article>
            <?php endforeach;

          elseif ($key === 'neighborhoods'):
            foreach ($items as $neighborhood): ?>
              <article>
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
