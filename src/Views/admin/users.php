<?php
$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath = '/admin/users';

$actorRole    = $authUser['role'];
$actorId      = $authUser['id'];
$isSuperAdmin = $actorRole === 'super_admin';

$canToggleStatus = static function (array $user) use ($actorRole, $actorId): bool {
    if ((int) $user['id_acc'] === $actorId) {
        return false;
    }

    if (!in_array($user['account_status'], ['active', 'suspended'], true)) {
        return false;
    }

    $targetRole = $user['role_name_rol'];

    if ($targetRole === 'super_admin') {
        return false;
    }

    if ($actorRole === 'admin' && $targetRole !== 'member') {
        return false;
    }

    return true;
};

$sortLabels = [
    'full_name'          => 'Name',
    'role_name_rol'      => 'Role',
    'account_status'     => 'Status',
    'overall_avg_rating' => 'Rating',
    'tools_owned'        => 'Tools Owned',
    'member_since'       => 'Joined',
];

$sortToColumn = [
    'full_name'          => 0,
    'role_name_rol'      => 1,
    'account_status'     => 2,
    'overall_avg_rating' => 3,
    'tools_owned'        => 4,
    'member_since'       => 5,
];

$isDeletedTab = $tab === 'deleted';
$ariaSortDir  = $dir === 'ASC' ? 'ascending' : 'descending';
$hasFilters   = $search !== null || $role !== null || ($status !== null && !$isDeletedTab);
?>

  <?php if ($flash !== null): ?>
    <p role="status" data-flash><?= htmlspecialchars($flash) ?></p>
  <?php endif; ?>

  <nav aria-label="User list tabs" data-user-tabs>
    <a href="/admin/users"<?= !$isDeletedTab ? ' aria-current="page"' : '' ?>>
      <i class="fa-solid fa-users" aria-hidden="true"></i> Active Users
    </a>
    <a href="/admin/users?tab=deleted"<?= $isDeletedTab ? ' aria-current="page"' : '' ?>>
      <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Deleted
      <?php if ($deletedCount > 0): ?>
        <span data-count><?= number_format($deletedCount) ?></span>
      <?php endif; ?>
    </a>
  </nav>

  <form method="get" action="/admin/users" role="search" aria-label="Filter and sort users" data-admin-filters>
    <fieldset>
      <legend class="visually-hidden">Filter and sort users</legend>

      <?php if ($isDeletedTab): ?>
        <input type="hidden" name="tab" value="deleted">
      <?php endif; ?>

      <div>
        <label for="users-search">Search</label>
        <input type="search" id="users-search" name="q"
               value="<?= htmlspecialchars($search ?? '') ?>"
               placeholder="Name or email…"
               autocomplete="off"
               data-suggest="admin" data-suggest-type="users">
      </div>

      <div>
        <label for="users-role">Role</label>
        <select id="users-role" name="role">
          <option value="">All Roles</option>
          <option value="member"<?= $role === 'member' ? ' selected' : '' ?>>Member</option>
          <option value="admin"<?= $role === 'admin' ? ' selected' : '' ?>>Admin</option>
          <option value="super_admin"<?= $role === 'super_admin' ? ' selected' : '' ?>>Super Admin</option>
        </select>
      </div>

      <?php if (!$isDeletedTab): ?>
        <div>
          <label for="users-status">Status</label>
          <select id="users-status" name="status">
            <option value="">All Statuses</option>
            <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Active</option>
            <option value="suspended"<?= $status === 'suspended' ? ' selected' : '' ?>>Suspended</option>
            <option value="pending"<?= $status === 'pending' ? ' selected' : '' ?>>Pending</option>
          </select>
        </div>
      <?php endif; ?>

      <div>
        <label for="users-sort">Sort By</label>
        <select id="users-sort" name="sort">
          <?php foreach ($sortLabels as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"<?= $sort === $value ? ' selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="users-dir">Direction</label>
        <select id="users-dir" name="dir">
          <option value="asc"<?= $dir === 'ASC' ? ' selected' : '' ?>>Ascending</option>
          <option value="desc"<?= $dir === 'DESC' ? ' selected' : '' ?>>Descending</option>
        </select>
      </div>

      <button type="submit" data-intent="primary" data-shape="pill">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply
      </button>
      <?php if ($hasFilters): ?>
        <a href="<?= htmlspecialchars($basePath . ($isDeletedTab ? '?tab=deleted' : '')) ?>" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
        </a>
      <?php endif; ?>
    </fieldset>
  </form>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>–<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        member<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($users)): ?>

    <table>
      <caption class="visually-hidden">Platform member accounts</caption>
      <thead>
        <tr>
          <?php
          $columns = ['Member', 'Role', 'Status', 'Rating', 'Tools', 'Joined', 'Actions'];
          foreach ($columns as $i => $label):
            $isSorted = isset($sortToColumn[$sort]) && $sortToColumn[$sort] === $i;
          ?>
            <th scope="col"<?= $isSorted ? ' aria-sort="' . $ariaSortDir . '"' : '' ?>><?= $label ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user):
          $isPending   = $user['account_status'] === 'pending';
          $isSuspended = $user['account_status'] === 'suspended';
          $isDeleted   = $user['account_status'] === 'deleted';
          $isPurged    = (int) ($user['is_purged_acc'] ?? 0) === 1;
          $userRole    = $user['role_name_rol'];
          $roleLabel   = match ($userRole) {
              'super_admin' => 'Super Admin',
              'admin'       => 'Admin',
              default       => 'Member',
          };
        ?>
          <tr<?php if ($isPurged) echo ' data-purged'; elseif ($isDeleted) echo ' data-deleted'; elseif ($isPending) echo ' data-pending'; elseif ($isSuspended) echo ' data-suspended'; ?>>
            <td data-label="Member">
              <a href="/profile/<?= (int) $user['id_acc'] ?>">
                <?= htmlspecialchars($user['full_name']) ?>
              </a>
              <small><?= htmlspecialchars($user['email_address_acc']) ?></small>
            </td>
            <td data-label="Role">
              <?php if ($isSuperAdmin && $userRole !== 'super_admin'
                        && (int) $user['id_acc'] !== $actorId): ?>
                <form method="post"
                      action="/admin/users/<?= (int) $user['id_acc'] ?>/role"
                      data-role-form>
                  <input type="hidden" name="csrf_token"
                         value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="return_to"
                         value="<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>">
                  <select name="role"
                          aria-label="Role for <?= htmlspecialchars($user['full_name']) ?>"
                          data-role-select
                          data-original="<?= htmlspecialchars($userRole) ?>">
                    <option value="member"<?= $userRole === 'member' ? ' selected' : '' ?>>Member</option>
                    <option value="admin"<?= $userRole === 'admin' ? ' selected' : '' ?>>Admin</option>
                  </select>
                  <button type="submit" data-intent="primary" data-size="sm">Update</button>
                </form>
              <?php else: ?>
                <span data-role="<?= htmlspecialchars($userRole) ?>">
                  <?= htmlspecialchars($roleLabel) ?>
                </span>
              <?php endif; ?>
            </td>
            <td data-label="Status">
              <span data-status="<?= htmlspecialchars($user['account_status']) ?>">
                <?= htmlspecialchars(ucfirst($user['account_status'])) ?>
              </span>
            </td>
            <td data-label="Rating">
              <?php if ((int) $user['total_rating_count'] > 0): ?>
                <span><?= htmlspecialchars($user['overall_avg_rating']) ?></span>
                <small>(<?= number_format((int) $user['total_rating_count']) ?>)</small>
              <?php else: ?>
                <span>—</span>
              <?php endif; ?>
            </td>
            <td data-label="Tools"><?= number_format((int) $user['tools_owned']) ?></td>
            <td data-label="Joined">
              <time datetime="<?= htmlspecialchars($user['member_since']) ?>">
                <?= htmlspecialchars(date('M j, Y', strtotime($user['member_since']))) ?>
              </time>
            </td>
            <td>
              <?php if ($isPurged): ?>
                <div data-actions>
                  <span data-badge="purged">Purged</span>
                </div>
              <?php elseif ($isDeleted && $isSuperAdmin && (int) $user['id_acc'] !== $actorId && $userRole !== 'super_admin'): ?>
                <div data-actions>
                  <button type="button"
                          data-purge-trigger
                          data-purge-id="<?= (int) $user['id_acc'] ?>"
                          data-purge-name="<?= htmlspecialchars($user['full_name']) ?>"
                          data-intent="danger"
                          data-size="sm">
                    <i class="fa-solid fa-skull-crossbones" aria-hidden="true"></i> Purge
                  </button>
                  <noscript>
                    <a href="/admin/users/<?= (int) $user['id_acc'] ?>/purge-confirm?<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>"
                       data-intent="danger" data-size="sm">
                      <i class="fa-solid fa-skull-crossbones" aria-hidden="true"></i> Purge
                    </a>
                  </noscript>
                </div>
              <?php elseif ($isPending): ?>
                <div data-actions>
                  <form method="post" action="/admin/users/<?= (int) $user['id_acc'] ?>/approve">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" data-approve data-intent="success" data-size="sm">
                      <i class="fa-solid fa-check" aria-hidden="true"></i> Approve
                    </button>
                  </form>
                  <form method="post" action="/admin/users/<?= (int) $user['id_acc'] ?>/deny">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" data-deny data-intent="danger" data-size="sm">
                      <i class="fa-solid fa-xmark" aria-hidden="true"></i> Deny
                    </button>
                  </form>
                </div>
              <?php elseif ($canToggleStatus($user)): ?>
                <div data-actions>
                  <?php if ($isSuspended): ?>
                    <form method="post" action="/admin/users/<?= (int) $user['id_acc'] ?>/status">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <button type="submit" data-activate data-intent="success" data-size="sm">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Activate
                      </button>
                    </form>
                    <?php if ($isSuperAdmin && $userRole !== 'super_admin'): ?>
                      <form method="post"
                            action="/admin/users/<?= (int) $user['id_acc'] ?>/delete"
                            data-delete-user-form>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>">
                        <button type="submit"
                                data-intent="danger"
                                data-size="sm"
                                data-delete-user-name="<?= htmlspecialchars($user['full_name']) ?>">
                          <i class="fa-solid fa-trash" aria-hidden="true"></i> Delete
                        </button>
                      </form>
                    <?php endif; ?>
                  <?php else: ?>
                    <form method="post" action="/admin/users/<?= (int) $user['id_acc'] ?>/status">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <button type="submit" data-suspend data-intent="danger" data-size="sm">
                        <i class="fa-solid fa-ban" aria-hidden="true"></i> Suspend
                      </button>
                    </form>
                    <a href="/profile/<?= (int) $user['id_acc'] ?>">View</a>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div data-actions>
                  <?php if ((int) $user['id_acc'] === $actorId): ?>
                    <a href="/profile/<?= (int) $user['id_acc'] ?>" data-own-profile>
                      <i class="fa-solid fa-user" aria-hidden="true"></i> My Profile
                    </a>
                  <?php else: ?>
                    <a href="/profile/<?= (int) $user['id_acc'] ?>">View Profile</a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php require BASE_PATH . '/src/Views/partials/pagination.php'; ?>

  <?php else: ?>

    <section aria-label="No users">
      <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
      <h2>No Members Found</h2>
      <p>No platform members match the current criteria.</p>
      <?php if ($search !== null || $role !== null || $status !== null): ?>
        <a href="/admin/users" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
        </a>
      <?php else: ?>
        <a href="<?= htmlspecialchars($backUrl) ?>" role="button" data-intent="secondary">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
        </a>
      <?php endif; ?>
    </section>

  <?php endif; ?>

  <?php if ($isSuperAdmin): ?>
    <dialog data-role-confirm aria-labelledby="role-confirm-title">
      <form method="dialog">
        <h2 id="role-confirm-title">Confirm Role Change</h2>
        <p data-role-confirm-message></p>
        <footer>
          <button type="submit" value="cancel" data-intent="ghost">Cancel</button>
          <button type="submit" value="confirm" data-intent="primary">Confirm</button>
        </footer>
      </form>
    </dialog>

    <dialog data-purge-confirm aria-labelledby="purge-confirm-title">
      <form method="post" data-purge-form>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="return_to" value="<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>">
        <h2 id="purge-confirm-title">Permanently Purge Account</h2>
        <p>This will <strong>permanently anonymize</strong> this account, resolve all active borrows,
           release held deposits, dismiss open disputes and incidents, and soft-delete all their tools.
           This action cannot be undone.</p>
        <p>Type <strong data-purge-expected-name></strong> to confirm:</p>
        <input type="text"
               name="confirm_name"
               data-purge-name-input
               autocomplete="off"
               required
               aria-label="Type the account name to confirm purge">
        <footer>
          <button type="button" data-intent="ghost" data-purge-cancel>Cancel</button>
          <button type="submit" data-intent="danger" data-purge-submit disabled><i class="fa-solid fa-skull-crossbones" aria-hidden="true"></i> Purge Account</button>
        </footer>
      </form>
    </dialog>
  <?php endif; ?>
