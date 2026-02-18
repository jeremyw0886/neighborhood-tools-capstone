<?php
/**
 * Dashboard — Lender sub-page: listed tools + incoming borrow requests.
 *
 * Variables from DashboardController::lender():
 *   $tools            array  Rows from tool_detail_v for this owner
 *   $incomingRequests array  Pending requests where user is lender
 *
 * Shared data:
 *   $authUser  array{id, name, first_name, role, avatar}
 */
?>

<section aria-labelledby="lender-heading">

  <header>
    <h1 id="lender-heading">
      <i class="fa-solid fa-hand-holding" aria-hidden="true"></i>
      My Tools
    </h1>
    <p>Manage your listed tools and respond to incoming borrow requests.</p>
  </header>

  <nav aria-label="Dashboard sections">
    <ul>
      <li><a href="/dashboard"         ><i class="fa-solid fa-gauge" aria-hidden="true"></i> Overview</a></li>
      <li><a href="/dashboard/lender"   aria-current="page"><i class="fa-solid fa-hand-holding" aria-hidden="true"></i> My Tools</a></li>
      <li><a href="/dashboard/borrower"><i class="fa-solid fa-hand" aria-hidden="true"></i> My Borrows</a></li>
      <li><a href="/dashboard/history" ><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> History</a></li>
    </ul>
  </nav>

  <?php if (!empty($_SESSION['borrow_success'])): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($_SESSION['borrow_success']) ?></p>
    <?php unset($_SESSION['borrow_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['borrow_errors']['general'])): ?>
    <p role="alert" data-flash="error"><?= htmlspecialchars($_SESSION['borrow_errors']['general']) ?></p>
    <?php unset($_SESSION['borrow_errors']); ?>
  <?php endif; ?>

  <?php if (!empty($incomingRequests)): ?>
    <section aria-labelledby="incoming-heading">
      <h2 id="incoming-heading">
        <i class="fa-solid fa-inbox" aria-hidden="true"></i>
        Incoming Requests (<?= count($incomingRequests) ?>)
      </h2>

      <table>
        <caption class="visually-hidden">Pending borrow requests for your tools</caption>
        <thead>
          <tr>
            <th scope="col">Tool</th>
            <th scope="col">Borrower</th>
            <th scope="col">Duration</th>
            <th scope="col">Waiting</th>
            <th scope="col">Borrower Rating</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($incomingRequests as $req): ?>
            <tr>
              <td><?= htmlspecialchars($req['tool_name_tol']) ?></td>
              <td>
                <a href="/profile/<?= (int) $req['borrower_id'] ?>">
                  <?= htmlspecialchars($req['borrower_name']) ?>
                </a>
              </td>
              <td><?= (int) $req['loan_duration_hours_bor'] ?> hrs</td>
              <td>
                <?php
                  $hoursPending = (int) $req['hours_pending'];
                  echo $hoursPending < 24
                    ? $hoursPending . 'h ago'
                    : (int) floor($hoursPending / 24) . 'd ago';
                ?>
              </td>
              <td>
                <?php
                  $bAvg = round((float) ($req['borrower_avg_rating'] ?? 0), 1);
                  $bCount = (int) ($req['borrower_rating_count'] ?? 0);
                ?>
                <?= $bCount > 0 ? $bAvg . '/5 (' . $bCount . ')' : 'No ratings' ?>
              </td>
              <td>
                <form method="post" action="/borrow/<?= (int) $req['id_bor'] ?>/approve">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <button type="submit">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Approve
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  <?php endif; ?>

  <section aria-labelledby="listed-tools-heading">
    <h2 id="listed-tools-heading">
      <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
      Listed Tools (<?= count($tools) ?>)
    </h2>

    <?php if (!empty($tools)): ?>
      <div role="list">
        <?php $cardHeadingLevel = 'h3'; ?>
        <?php foreach ($tools as $tool): ?>
          <?php require BASE_PATH . '/src/Views/partials/tool-card.php'; ?>
        <?php endforeach; ?>
        <?php unset($cardHeadingLevel); ?>
      </div>
    <?php else: ?>
      <p>You haven&rsquo;t listed any tools yet.</p>
      <a href="/tools/create" role="button">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> List Your First Tool
      </a>
    <?php endif; ?>
  </section>

</section>
