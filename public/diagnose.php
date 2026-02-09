<?php
/**
 * Diagnostic script — run on production to identify browse tools failures.
 *
 * Upload to public/ and visit /diagnose.php in a browser.
 * DELETE THIS FILE after diagnosis — it exposes internal details.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

// Bootstrap the app so Database::connection() and constants work
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/bootstrap.php';

use App\Core\Database;

echo "=== NeighborhoodTools Production Diagnostic ===\n\n";

// 1. DB connection
echo "1. Database connection: ";
try {
    $pdo = Database::connection();
    echo "OK\n";
} catch (Throwable $e) {
    echo "FAIL — " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Current session sql_mode
echo "\n2. Session sql_mode:\n";
try {
    $mode = $pdo->query("SELECT @@sql_mode AS mode")->fetch();
    echo "   " . ($mode['mode'] ?: '(empty)') . "\n";
} catch (Throwable $e) {
    echo "   FAIL — " . $e->getMessage() . "\n";
}

// 3. MySQL version
echo "\n3. MySQL version: ";
try {
    $ver = $pdo->query("SELECT VERSION() AS v")->fetch();
    echo $ver['v'] . "\n";
} catch (Throwable $e) {
    echo "FAIL — " . $e->getMessage() . "\n";
}

// 4. Check if key views exist
echo "\n4. Key views:\n";
$views = ['tool_detail_v', 'category_summary_v', 'available_tool_v', 'active_account_v'];
foreach ($views as $v) {
    echo "   $v: ";
    try {
        $pdo->query("SELECT 1 FROM $v LIMIT 1");
        echo "OK\n";
    } catch (Throwable $e) {
        echo "FAIL — " . $e->getMessage() . "\n";
    }
}

// 5. Check if key functions exist
echo "\n5. Helper functions:\n";
$functions = ['fn_get_account_status_id', 'fn_get_borrow_status_id'];
foreach ($functions as $fn) {
    echo "   $fn(): ";
    try {
        $pdo->query("SELECT $fn('active')")->fetch();
        echo "OK\n";
    } catch (Throwable $e) {
        echo "FAIL — " . $e->getMessage() . "\n";
    }
}

// 6. Check SP exists and runs
echo "\n6. sp_search_available_tools():\n";
echo "   a) SP exists: ";
try {
    $check = $pdo->query(
        "SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES "
        . "WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = 'sp_search_available_tools'"
    )->fetch();
    echo $check ? "YES\n" : "NO — SP not found!\n";
} catch (Throwable $e) {
    echo "FAIL — " . $e->getMessage() . "\n";
}

echo "   b) SP baked sql_mode: ";
try {
    $spMode = $pdo->query(
        "SELECT SQL_MODE FROM INFORMATION_SCHEMA.ROUTINES "
        . "WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = 'sp_search_available_tools'"
    )->fetch();
    echo ($spMode ? ($spMode['SQL_MODE'] ?: '(empty)') : 'N/A') . "\n";
} catch (Throwable $e) {
    echo "FAIL — " . $e->getMessage() . "\n";
}

echo "   c) CALL with NULL params: ";
try {
    $stmt = $pdo->prepare('CALL sp_search_available_tools(NULL, NULL, NULL, NULL, 5, 0)');
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $stmt->closeCursor();
    echo "OK — returned " . count($rows) . " row(s)\n";
} catch (Throwable $e) {
    echo "FAIL — " . $e->getMessage() . "\n";
}

// 7. Test searchCount raw query
echo "\n7. searchCount raw query:\n";
try {
    $sql = "SELECT COUNT(DISTINCT t.id_tol) AS cnt
            FROM tool_tol t
            JOIN account_acc a ON t.id_acc_tol = a.id_acc
            WHERE t.is_available_tol = TRUE
              AND a.id_ast_acc != fn_get_account_status_id('deleted')
              AND NOT EXISTS (
                  SELECT 1 FROM borrow_bor b
                  WHERE b.id_tol_bor = t.id_tol
                    AND b.id_bst_bor IN (
                        fn_get_borrow_status_id('requested'),
                        fn_get_borrow_status_id('approved'),
                        fn_get_borrow_status_id('borrowed')
                    )
              )
              AND NOT EXISTS (
                  SELECT 1 FROM availability_block_avb avb
                  WHERE avb.id_tol_avb = t.id_tol
                    AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
              )";
    $cnt = $pdo->query($sql)->fetch();
    echo "   OK — " . $cnt['cnt'] . " available tool(s)\n";
} catch (Throwable $e) {
    echo "   FAIL — " . $e->getMessage() . "\n";
}

// 8. Test category_summary_v
echo "\n8. category_summary_v:\n";
try {
    $cats = $pdo->query("SELECT * FROM category_summary_v ORDER BY category_name_cat ASC")->fetchAll();
    echo "   OK — " . count($cats) . " categor" . (count($cats) !== 1 ? 'ies' : 'y') . "\n";
} catch (Throwable $e) {
    echo "   FAIL — " . $e->getMessage() . "\n";
}

// 9. Check all routine sql_modes for ONLY_FULL_GROUP_BY
echo "\n9. Routines with ONLY_FULL_GROUP_BY baked in:\n";
try {
    $routines = $pdo->query(
        "SELECT ROUTINE_NAME, ROUTINE_TYPE, SQL_MODE
         FROM INFORMATION_SCHEMA.ROUTINES
         WHERE ROUTINE_SCHEMA = DATABASE()
           AND SQL_MODE LIKE '%ONLY_FULL_GROUP_BY%'
         ORDER BY ROUTINE_TYPE, ROUTINE_NAME"
    )->fetchAll();
    if (empty($routines)) {
        echo "   None — all clear\n";
    } else {
        echo "   Found " . count($routines) . " routine(s) with ONLY_FULL_GROUP_BY:\n";
        foreach ($routines as $r) {
            echo "   - [{$r['ROUTINE_TYPE']}] {$r['ROUTINE_NAME']}\n";
        }
    }
} catch (Throwable $e) {
    echo "   FAIL — " . $e->getMessage() . "\n";
}

// 10. Check collation
echo "\n10. Database collation:\n";
try {
    $coll = $pdo->query(
        "SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
         FROM INFORMATION_SCHEMA.SCHEMATA
         WHERE SCHEMA_NAME = DATABASE()"
    )->fetch();
    echo "   Charset: " . $coll['DEFAULT_CHARACTER_SET_NAME'] . "\n";
    echo "   Collation: " . $coll['DEFAULT_COLLATION_NAME'] . "\n";
} catch (Throwable $e) {
    echo "   FAIL — " . $e->getMessage() . "\n";
}

echo "\n=== Diagnostic complete ===\n";
echo "DELETE THIS FILE after reviewing results.\n";
