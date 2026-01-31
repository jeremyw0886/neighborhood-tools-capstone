<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Demo - NeighborhoodTools</title>
    <meta name="description" content="Database connection demonstration for NeighborhoodTools">
    <link rel="stylesheet" href="/css/style.css?v=2">
</head>
<body>
    <main>
        <h1>Database Demo</h1>

        <?php if (isset($pdo)): ?>
            <p class="demo-status">Database Connection Successful</p>

            <dl class="demo-info">
                <div>
                    <dt>Database:</dt>
                    <dd><?= htmlspecialchars($dbname) ?></dd>
                </div>
                <div>
                    <dt>Status:</dt>
                    <dd>In Development</dd>
                </div>
            </dl>

            <section aria-labelledby="conditions-heading">
                <h2 id="conditions-heading">Tool Conditions</h2>
                <?php
                $stmt = $pdo->query("SELECT id_tcd, condition_name_tcd FROM tool_condition_tcd ORDER BY id_tcd");
                $conditions = $stmt->fetchAll();
                ?>
                <table class="demo-table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Condition</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conditions as $condition): ?>
                            <tr>
                                <td><?= htmlspecialchars($condition['id_tcd']) ?></td>
                                <td><?= htmlspecialchars($condition['condition_name_tcd']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

        <?php else: ?>
            <p class="demo-error">Database connection failed.</p>
        <?php endif; ?>

        <nav class="back-link">
            <a href="/">← Back to Home</a>
        </nav>
    </main>

    <footer>
        <p><small>&copy; 2026 NeighborhoodTools</small></p>
    </footer>
</body>
</html>
