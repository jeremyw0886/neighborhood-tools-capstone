<?php
/**
 * Admin — User management with reputation data and role info.
 *
 * Variables from AdminController::users():
 *   $users       array   Rows from Account::getAllForAdmin() (reputation + role_name_rol)
 *   $totalCount  int     Total members in the system
 *   $page        int     Current page (1-based)
 *   $totalPages  int     Total pages
 *   $perPage     int     Results per page (12)
 *   $flash       ?string One-time status message (approve/deny/status feedback)
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

$paginationUrl = static fn(int $pageNum): string =>
    '/admin/users' . ($pageNum > 1 ? '?page=' . $pageNum : '');

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

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= $rangeStart ?>–<?= $rangeEnd ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        member<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (!empty($users)): ?>

    <table>
      <thead>
        <tr>
          <th scope="col">Member</th>
          <th scope="col">Role</th>
          <th scope="col">Status</th>
          <th scope="col">Rating</th>
          <th scope="col">Tools</th>
          <th scope="col">Joined</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user):
          $isPending   = $user['account_status'] === 'pending';
          $isSuspended = $user['account_status'] === 'suspended';
          $role        = $user['role_name_rol'];
          $roleLabel   = match ($role) {
              'super_admin' => 'Super Admin',
              'admin'       => 'Admin',
              default       => 'Member',
          };
        ?>
          <tr<?php if ($isPending) echo ' data-pending'; elseif ($isSuspended) echo ' data-suspended'; ?>>
            <td>
              <a href="/profile/<?= (int) $user['id_acc'] ?>">
                <?= htmlspecialchars($user['full_name']) ?>
              </a>
              <small><?= htmlspecialchars($user['email_address_acc']) ?></small>
            </td>
            <td>
              <span data-role="<?= htmlspecialchars($role) ?>">
                <?= htmlspecialchars($roleLabel) ?>
              </span>
            </td>
            <td>
              <span data-status="<?= htmlspecialchars($user['account_status']) ?>">
                <?= htmlspecialchars(ucfirst($user['account_status'])) ?>
              </span>
            </td>
            <td>
              <?php if ((int) $user['total_rating_count'] > 0): ?>
                <span><?= htmlspecialchars($user['overall_avg_rating']) ?></span>
                <small>(<?= number_format((int) $user['total_rating_count']) ?>)</small>
              <?php else: ?>
                <span>—</span>
              <?php endif; ?>
            </td>
            <td><?= number_format((int) $user['tools_owned']) ?></td>
            <td>
              <time datetime="<?= htmlspecialchars($user['member_since']) ?>">
                <?= htmlspecialchars(date('M j, Y', strtotime($user['member_since']))) ?>
              </time>
            </td>
            <td>
              <?php if ($isPending): ?>
                <div data-actions>
                  <form method="post" action="/admin/users/<?= (int) $user['id_acc'] ?>/approve">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" data-approve>
                      <i class="fa-solid fa-check" aria-hidden="true"></i> Approve
                    </button>
                  </form>
                  <form method="post" action="/admin/users/<?= (int) $user['id_acc'] ?>/deny">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" data-deny>
                      <i class="fa-solid fa-xmark" aria-hidden="true"></i> Deny
                    </button>
                  </form>
                </div>
              <?php elseif ($canToggleStatus($user)): ?>
                <div data-actions>
                  <?php if ($isSuspended): ?>
                    <form method="post" action="/admin/users/<?= (int) $user['id_acc'] ?>/status">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <button type="submit" data-activate>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Activate
                      </button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="/admin/users/<?= (int) $user['id_acc'] ?>/status">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <button type="submit" data-suspend>
                        <i class="fa-solid fa-ban" aria-hidden="true"></i> Suspend
                      </button>
                    </form>
                  <?php endif; ?>
                  <a href="/profile/<?= (int) $user['id_acc'] ?>">View</a>
                </div>
              <?php else: ?>
                <a href="/profile/<?= (int) $user['id_acc'] ?>">View Profile</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Pagination">
        <ul>

          <?php if ($page > 1): ?>
            <li>
              <a href="<?= $paginationUrl($page - 1) ?>"
                 aria-label="Go to previous page">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span>Previous</span>
              </a>
            </li>
          <?php else: ?>
            <li>
              <span aria-disabled="true" aria-label="No previous page">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span>Previous</span>
              </span>
            </li>
          <?php endif; ?>

          <?php
          $windowSize = 2;
          $startPage  = max(1, $page - $windowSize);
          $endPage    = min($totalPages, $page + $windowSize);

          if ($startPage > 1): ?>
            <li>
              <a href="<?= $paginationUrl(1) ?>" aria-label="Go to page 1">1</a>
            </li>
            <?php if ($startPage > 2): ?>
              <li><span aria-hidden="true">&hellip;</span></li>
            <?php endif; ?>
          <?php endif; ?>

          <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li>
              <?php if ($i === $page): ?>
                <a href="<?= $paginationUrl($i) ?>"
                   aria-current="page"
                   aria-label="Page <?= $i ?>, current page"><?= $i ?></a>
              <?php else: ?>
                <a href="<?= $paginationUrl($i) ?>"
                   aria-label="Go to page <?= $i ?>"><?= $i ?></a>
              <?php endif; ?>
            </li>
          <?php endfor; ?>

          <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
              <li><span aria-hidden="true">&hellip;</span></li>
            <?php endif; ?>
            <li>
              <a href="<?= $paginationUrl($totalPages) ?>"
                 aria-label="Go to page <?= $totalPages ?>"><?= $totalPages ?></a>
            </li>
          <?php endif; ?>

          <?php if ($page < $totalPages): ?>
            <li>
              <a href="<?= $paginationUrl($page + 1) ?>"
                 aria-label="Go to next page">
                <span>Next</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </a>
            </li>
          <?php else: ?>
            <li>
              <span aria-disabled="true" aria-label="No next page">
                <span>Next</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </span>
            </li>
          <?php endif; ?>

        </ul>
      </nav>
    <?php endif; ?>

  <?php else: ?>

    <section aria-label="No users">
      <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
      <h2>No Members Found</h2>
      <p>No platform members match the current criteria.</p>
      <a href="/admin" role="button">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Dashboard
      </a>
    </section>

  <?php endif; ?>

</section>
