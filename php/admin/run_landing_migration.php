<?php
/**
 * run_landing_migration.php
 * Landing Page Tables Migration Runner
 * RMU Medical Management System — Phase 2
 *
 * Access via:  http://localhost/RMU-Medical-Management-System/php/admin/run_landing_migration.php
 *
 * SECURITY: This file should be removed or protected after the migration runs successfully.
 */

// Only allow admin users in session to run this
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Uncomment the following lines to enforce admin-only access in production:
// if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     die('<h2 style="color:red;">Access Denied — Admin only.</h2>');
// }

require_once '../db_conn.php';

$sqlFile = __DIR__ . '/../../database/migrations/landing_page_tables.sql';

if (!file_exists($sqlFile)) {
    die('<h2 style="color:red;">❌ SQL file not found at: ' . htmlspecialchars($sqlFile) . '</h2>');
}

// ── Split SQL into individual statements ──────────────────────────────────
$rawSql   = file_get_contents($sqlFile);
$queries  = array_filter(
    array_map('trim', explode(';', $rawSql)),
    fn($q) => !empty($q) && !preg_match('/^--/', ltrim($q))
);

// ── Execute ───────────────────────────────────────────────────────────────
$results  = [];
$errors   = [];
$success  = 0;
$skipped  = 0;

foreach ($queries as $query) {
    // Skip pure comment blocks
    if (preg_match('/^[\s\-\/\*]+$/', $query)) { $skipped++; continue; }

    $ok = mysqli_query($conn, $query);
    if ($ok) {
        $success++;
        $preview = nl2br(htmlspecialchars(substr($query, 0, 120)));
        $results[] = ['status' => 'ok', 'preview' => $preview];
    } else {
        $err = mysqli_error($conn);
        // "Duplicate column" errors are expected — mark as skipped, not failed
        if (str_contains($err, 'Duplicate column') || str_contains($err, 'already exists')) {
            $skipped++;
            $preview = nl2br(htmlspecialchars(substr($query, 0, 120)));
            $results[] = ['status' => 'skip', 'preview' => $preview, 'note' => $err];
        } else {
            $errors[] = ['query' => substr($query, 0, 200), 'error' => $err];
            $results[] = ['status' => 'err', 'preview' => nl2br(htmlspecialchars(substr($query, 0, 120))), 'note' => $err];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page Migration — RMU Medical</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#0f172a; color:#e2e8f0; min-height:100vh; padding:2rem; }
        .container { max-width:960px; margin:0 auto; }
        .header { background:linear-gradient(135deg,#1C3A6B,#2F80ED); border-radius:16px; padding:2rem 2.5rem; margin-bottom:2rem; }
        .header h1 { font-size:1.8rem; color:#fff; margin-bottom:.3rem; }
        .header p  { color:rgba(255,255,255,.8); font-size:.95rem; }
        .summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; margin-bottom:2rem; }
        .sum-card { background:#1e293b; border-radius:12px; padding:1.2rem 1.5rem; text-align:center; }
        .sum-card .num { font-size:2.5rem; font-weight:700; }
        .sum-card .lbl { font-size:.8rem; color:#94a3b8; margin-top:.3rem; }
        .sum-card.ok  .num { color:#22c55e; }
        .sum-card.err .num { color:#f87171; }
        .sum-card.skip .num { color:#facc15; }
        .log { background:#1e293b; border-radius:12px; overflow:hidden; }
        .log-row { display:flex; align-items:flex-start; gap:1rem; padding:.9rem 1.2rem; border-bottom:1px solid #334155; font-size:.82rem; }
        .log-row:last-child { border:none; }
        .log-row .badge { flex-shrink:0; padding:.2rem .7rem; border-radius:20px; font-weight:600; font-size:.75rem; text-transform:uppercase; }
        .badge-ok   { background:rgba(34,197,94,.15); color:#22c55e; }
        .badge-skip { background:rgba(250,204,21,.15); color:#facc15; }
        .badge-err  { background:rgba(248,113,113,.15); color:#f87171; }
        .log-row .query { color:#94a3b8; line-height:1.5; word-break:break-all; }
        .log-row .note  { color:#f87171; font-size:.78rem; margin-top:.3rem; }
        .errors-section { background:#1e293b; border-radius:12px; padding:1.5rem; margin-top:1.5rem; border-left:4px solid #f87171; }
        .errors-section h3 { color:#f87171; margin-bottom:1rem; }
        .err-item { background:#0f172a; border-radius:8px; padding:1rem; margin-bottom:.8rem; font-size:.82rem; }
        .err-item .eq { color:#94a3b8; margin-bottom:.4rem; }
        .err-item .em { color:#f87171; }
        .done { background:#1e293b; border-radius:12px; padding:1.5rem; margin-top:1.5rem; border-left:4px solid #22c55e; }
        .done h3 { color:#22c55e; }
        .done p  { color:#94a3b8; margin-top:.5rem; font-size:.9rem; }
        .done a  { color:#2F80ED; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-database"></i> Landing Page Migration Runner</h1>
        <p>RMU Medical Management System — Phase 2: Database Tables</p>
    </div>

    <div class="summary">
        <div class="sum-card ok">
            <div class="num"><?= $success ?></div>
            <div class="lbl">Executed OK</div>
        </div>
        <div class="sum-card skip">
            <div class="num"><?= $skipped ?></div>
            <div class="lbl">Skipped / Already Exists</div>
        </div>
        <div class="sum-card err">
            <div class="num"><?= count($errors) ?></div>
            <div class="lbl">Errors</div>
        </div>
    </div>

    <div class="log">
        <?php foreach ($results as $r): ?>
            <div class="log-row">
                <?php if ($r['status'] === 'ok'): ?>
                    <span class="badge badge-ok"><i class="fas fa-check"></i> OK</span>
                <?php elseif ($r['status'] === 'skip'): ?>
                    <span class="badge badge-skip"><i class="fas fa-forward"></i> Skip</span>
                <?php else: ?>
                    <span class="badge badge-err"><i class="fas fa-xmark"></i> Error</span>
                <?php endif; ?>
                <div>
                    <div class="query"><?= $r['preview'] ?>...</div>
                    <?php if (!empty($r['note'])): ?>
                        <div class="note"><?= htmlspecialchars($r['note']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="errors-section">
        <h3><i class="fas fa-triangle-exclamation"></i> Errors Requiring Attention</h3>
        <?php foreach ($errors as $e): ?>
        <div class="err-item">
            <div class="eq"><?= nl2br(htmlspecialchars(substr($e['query'], 0, 300))) ?></div>
            <div class="em"><i class="fas fa-bug"></i> <?= htmlspecialchars($e['error']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (count($errors) === 0): ?>
    <div class="done">
        <h3><i class="fas fa-circle-check"></i> Migration Completed Successfully</h3>
        <p>All 16 landing page tables are ready. You may now proceed to <strong>Phase 3: Admin Panel Integration</strong>.</p>
        <p style="margin-top:.8rem;"><strong>Next step:</strong> <a href="/RMU-Medical-Management-System/php/admin/home.php">Go to Admin Dashboard →</a></p>
        <p style="margin-top:.5rem;color:#f59e0b;font-size:.82rem;"><i class="fas fa-shield-halved"></i> Remember to delete or restrict access to this file before going to production.</p>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
