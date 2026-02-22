<?php
/**
 * Admin — TOS management with non-compliant user listing.
 *
 * Variables from AdminController::tos():
 *   $users       array   Rows from Tos::getNonCompliantUsers() via tos_acceptance_required_v
 *   $totalCount  int     Total non-compliant users
 *   $page        int     Current page (1-based)
 *   $totalPages  int     Total pages
 *   $perPage     int     Results per page (12)
 *
 * Each user row contains:
 *   id_acc, full_name, email_address_acc, account_status,
 *   last_login_at_acc, created_at_acc,
 *   last_tos_accepted_at, last_accepted_version
 *
 * Shared data:
 *   $currentTos   ?array  Current TOS version from getSharedData()
 *   $currentPage  string
 *   $backUrl      string
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$paginationUrl = static fn(int $pageNum): string =>
    '/admin/tos' . ($pageNum > 1 ? '?page=' . $pageNum : '');
?>

<section aria-labelledby="admin-tos-heading">

  <header>
    <h1 id="admin-tos-heading">
      <i class="fa-solid fa-file-contract" aria-hidden="true"></i>
      Manage Terms of Service
    </h1>
    <p>Current version details and member acceptance status.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <?php if ($currentTos !== null): ?>
    <section aria-labelledby="tos-version-heading">
      <h2 id="tos-version-heading">
        <i class="fa-solid fa-scroll" aria-hidden="true"></i>
        Current Version
      </h2>

      <dl>
        <div>
          <dt>Version</dt>
          <dd><?= htmlspecialchars($currentTos['version_tos']) ?></dd>
        </div>
        <div>
          <dt>Title</dt>
          <dd><?= htmlspecialchars($currentTos['title_tos']) ?></dd>
        </div>
        <div>
          <dt>Effective</dt>
          <dd>
            <time datetime="<?= htmlspecialchars($currentTos['effective_at_tos']) ?>">
              <?= htmlspecialchars(date('M j, Y', strtotime($currentTos['effective_at_tos']))) ?>
            </time>
          </dd>
        </div>
        <div>
          <dt>Acceptances</dt>
          <dd><?= number_format((int) $currentTos['total_acceptances']) ?></dd>
        </div>
        <div>
          <dt>Created by</dt>
          <dd><?= htmlspecialchars($currentTos['created_by_name']) ?></dd>
        </div>
      </dl>
    </section>
  <?php else: ?>
    <p>No active Terms of Service version found.</p>
  <?php endif; ?>

  <section aria-labelledby="tos-noncompliant-heading">
    <h2 id="tos-noncompliant-heading">
      <i class="fa-solid fa-user-xmark" aria-hidden="true"></i>
      Non-Compliant Members
    </h2>

    <div aria-live="polite" aria-atomic="true">
      <?php if ($totalCount > 0): ?>
        <p>
          Showing <strong><?= $rangeStart ?>–<?= $rangeEnd ?></strong> of
          <strong><?= number_format($totalCount) ?></strong>
          member<?= $totalCount !== 1 ? 's' : '' ?> who
          ha<?= $totalCount !== 1 ? 've' : 's' ?> not accepted the current terms
        </p>
      <?php endif; ?>
    </div>

    <?php if (!empty($users)): ?>

      <table>
        <thead>
          <tr>
            <th scope="col">Member</th>
            <th scope="col">Last Login</th>
            <th scope="col">Last Accepted</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td>
                <a href="/profile/<?= (int) $user['id_acc'] ?>">
                  <?= htmlspecialchars($user['full_name']) ?>
                </a>
                <small><?= htmlspecialchars($user['email_address_acc']) ?></small>
              </td>
              <td>
                <?php if ($user['last_login_at_acc'] !== null): ?>
                  <time datetime="<?= htmlspecialchars($user['last_login_at_acc']) ?>">
                    <?= htmlspecialchars(date('M j, Y', strtotime($user['last_login_at_acc']))) ?>
                  </time>
                <?php else: ?>
                  <span>Never</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($user['last_accepted_version'] !== null): ?>
                  <span>v<?= htmlspecialchars($user['last_accepted_version']) ?></span>
                  <small>
                    <time datetime="<?= htmlspecialchars($user['last_tos_accepted_at']) ?>">
                      <?= htmlspecialchars(date('M j, Y', strtotime($user['last_tos_accepted_at']))) ?>
                    </time>
                  </small>
                <?php else: ?>
                  <span>None</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="/profile/<?= (int) $user['id_acc'] ?>">
                  View Profile
                </a>
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

      <section aria-label="All compliant">
        <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
        <h3>All Members Compliant</h3>
        <p>Every active member has accepted the current Terms of Service.</p>
        <a href="/admin" role="button">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Dashboard
        </a>
      </section>

    <?php endif; ?>
  </section>

</section>
