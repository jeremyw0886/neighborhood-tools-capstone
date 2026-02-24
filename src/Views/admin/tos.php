<?php
/**
 * Admin — TOS management with sortable non-compliant user listing.
 *
 * Variables from AdminController::tos():
 *   $users        array   Rows from Tos::getNonCompliantUsers() via tos_acceptance_required_v
 *   $totalCount   int     Total non-compliant users
 *   $page         int     Current page (1-based)
 *   $totalPages   int     Total pages
 *   $perPage      int     Results per page (12)
 *   $sort         string  Active sort column
 *   $dir          string  Active sort direction (ASC|DESC)
 *   $filterParams array   Non-null filter params for pagination URLs
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

$flash      = $_SESSION['admin_tos_flash'] ?? null;
unset($_SESSION['admin_tos_flash']);

$isSuperAdmin = ($authUser['role'] ?? '') === 'super_admin';

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath = '/admin/tos';

$sortLabels = [
    'full_name'              => 'Name',
    'last_login_at_acc'      => 'Last Login',
    'last_accepted_version'  => 'Last Accepted Version',
];

$sortToColumn = [
    'full_name'             => 0,
    'last_login_at_acc'     => 1,
    'last_accepted_version' => 2,
];

$ariaSortDir = $dir === 'ASC' ? 'ascending' : 'descending';
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

  <?php if ($flash !== null): ?>
    <p data-flash role="status"><?= htmlspecialchars($flash) ?></p>
  <?php endif; ?>

  <?php if ($isSuperAdmin): ?>
    <div data-actions>
      <a href="/admin/tos/create">
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Create New Version
      </a>
    </div>
  <?php endif; ?>

  <form method="get" action="/admin/tos" role="search" aria-label="Sort non-compliant members" data-admin-filters>
    <fieldset>
      <legend class="visually-hidden">Sort non-compliant members</legend>

      <div>
        <label for="tos-sort">Sort By</label>
        <select id="tos-sort" name="sort">
          <?php foreach ($sortLabels as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"<?= $sort === $value ? ' selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="tos-dir">Direction</label>
        <select id="tos-dir" name="dir">
          <option value="asc"<?= $dir === 'ASC' ? ' selected' : '' ?>>Ascending</option>
          <option value="desc"<?= $dir === 'DESC' ? ' selected' : '' ?>>Descending</option>
        </select>
      </div>

      <button type="submit">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply
      </button>
    </fieldset>
  </form>

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
          Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>–<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
          <strong><?= number_format($totalCount) ?></strong>
          member<?= $totalCount !== 1 ? 's' : '' ?> who
          ha<?= $totalCount !== 1 ? 've' : 's' ?> not accepted the current terms
        </p>
      <?php endif; ?>
    </div>

    <?php if (!empty($users)): ?>

      <table>
        <caption class="visually-hidden">Members who have not accepted the current terms of service</caption>
        <thead>
          <tr>
            <?php
            $columns = ['Member', 'Last Login', 'Accepted', 'Actions'];
            foreach ($columns as $i => $label):
              $isSorted = isset($sortToColumn[$sort]) && $sortToColumn[$sort] === $i;
            ?>
              <th scope="col"<?= $isSorted ? ' aria-sort="' . $ariaSortDir . '"' : '' ?>><?= $label ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td data-label="Member">
                <a href="/profile/<?= (int) $user['id_acc'] ?>">
                  <?= htmlspecialchars($user['full_name']) ?>
                </a>
                <small><?= htmlspecialchars($user['email_address_acc']) ?></small>
              </td>
              <td data-label="Last Login">
                <?php if ($user['last_login_at_acc'] !== null): ?>
                  <time datetime="<?= htmlspecialchars($user['last_login_at_acc']) ?>">
                    <?= htmlspecialchars(date('M j, Y', strtotime($user['last_login_at_acc']))) ?>
                  </time>
                <?php else: ?>
                  <span>Never</span>
                <?php endif; ?>
              </td>
              <td data-label="Accepted">
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

      <?php require BASE_PATH . '/src/Views/partials/pagination.php'; ?>

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
