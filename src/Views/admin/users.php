<?php
/**
 * Admin — User management with reputation data and role info.
 *
 * Variables from AdminController::users():
 *   $users        array   Rows from Account::getAllForAdmin() (reputation + role_name_rol)
 *   $totalCount   int     Total members matching current filters
 *   $page         int     Current page (1-based)
 *   $totalPages   int     Total pages
 *   $perPage      int     Results per page (12)
 *   $flash        ?string One-time status message (approve/deny/status feedback)
 *   $search       ?string Active search query or null
 *   $role         ?string Active role filter or null
 *   $status       ?string Active status filter or null
 *   $sort         string  Active sort column
 *   $dir          string  Active sort direction (ASC|DESC)
 *   $filterParams array   Non-null filter params for pagination URLs
 *
 * Each user row contains:
 *   id_acc, full_name, email_address_acc, account_status, role_name_rol,
 *   member_since, lender_avg_rating, lender_rating_count,
 *   borrower_avg_rating, borrower_rating_count, overall_avg_rating,
 *   total_rating_count, tools_owned, completed_borrows, refreshed_at
 *
 * Shared data:
 *   $csrfToken    string
 *   $currentPage  string
 *   $authUser     array{id: int, role: string, ...}
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath = '/admin/users';

$actorRole = $authUser['role'];
$actorId   = $authUser['id'];

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

$ariaSortDir = $dir === 'ASC' ? 'ascending' : 'descending';
$hasFilters  = $search !== null || $role !== null || $status !== null;
?>

<section aria-labelledby="admin-users-heading">

  <header>
    <h1 id="admin-users-heading">
      <i class="fa-solid fa-users" aria-hidden="true"></i>
      Manage Users
    </h1>
    <p>Platform members with rating summaries and account status management.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <?php if ($flash !== null): ?>
    <p role="status" data-flash><?= htmlspecialchars($flash) ?></p>
  <?php endif; ?>

  <form method="get" action="/admin/users" role="search" aria-label="Filter and sort users" data-admin-filters>
    <fieldset>
      <legend class="visually-hidden">Filter and sort users</legend>

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

      <div>
        <label for="users-status">Status</label>
        <select id="users-status" name="status">
          <option value="">All Statuses</option>
          <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Active</option>
          <option value="suspended"<?= $status === 'suspended' ? ' selected' : '' ?>>Suspended</option>
          <option value="pending"<?= $status === 'pending' ? ' selected' : '' ?>>Pending</option>
        </select>
      </div>

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
        <a href="<?= htmlspecialchars($basePath) ?>" role="button" data-intent="ghost">
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
          $userRole    = $user['role_name_rol'];
          $roleLabel   = match ($userRole) {
              'super_admin' => 'Super Admin',
              'admin'       => 'Admin',
              default       => 'Member',
          };
        ?>
          <tr<?php if ($isPending) echo ' data-pending'; elseif ($isSuspended) echo ' data-suspended'; ?>>
            <td data-label="Member">
              <a href="/profile/<?= (int) $user['id_acc'] ?>">
                <?= htmlspecialchars($user['full_name']) ?>
              </a>
              <small><?= htmlspecialchars($user['email_address_acc']) ?></small>
            </td>
            <td data-label="Role">
              <span data-role="<?= htmlspecialchars($userRole) ?>">
                <?= htmlspecialchars($roleLabel) ?>
              </span>
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
              <?php if ($isPending): ?>
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
          <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Clear Filters
        </a>
      <?php else: ?>
        <a href="<?= htmlspecialchars($backUrl) ?>" role="button" data-intent="secondary">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
        </a>
      <?php endif; ?>
    </section>

  <?php endif; ?>

</div>
</section>
