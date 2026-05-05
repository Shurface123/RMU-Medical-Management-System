<?php
/**
 * DATABASE BACKUP UTILITY
 * Creates SQL backup of the entire database
 */

require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';
require_once '../classes/AuditLogger.php';

$active_page = 'backup_management';
$page_title = 'System Backups';

$auditLogger = new AuditLogger($conn);

// Database credentials
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'Confrontation@433';
$dbName = 'rmu_medical_sickbay';

// Backup directory
$backupDir = __DIR__ . '/../../backups/';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$message = '';
$error = '';

// Handle backup creation
if (isset($_POST['create_backup'])) {
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . 'backup_' . $timestamp . '.sql';

    // Get all tables
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }

    $sqlDump = "-- RMU Medical Sickbay Database Backup\n";
    $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sqlDump .= "-- Database: $dbName\n\n";
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Loop through tables
    foreach ($tables as $table) {
        // Drop table statement
        $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";

        // Create table statement
        $createTable = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $row = mysqli_fetch_row($createTable);
        $sqlDump .= $row[1] . ";\n\n";

        // Insert data
        $rows = mysqli_query($conn, "SELECT * FROM `$table`");
        $numFields = mysqli_num_fields($rows);

        if (mysqli_num_rows($rows) > 0) {
            $sqlDump .= "INSERT INTO `$table` VALUES\n";
            $rowCount = 0;

            while ($row = mysqli_fetch_row($rows)) {
                $sqlDump .= "(";
                for ($i = 0; $i < $numFields; $i++) {
                    $val = $row[$i];
                    if (isset($val)) {
                        $val = addslashes($val);
                        $val = str_replace("\n", "\\n", $val);
                        $sqlDump .= '"' . $val . '"';
                    }
                    else {
                        $sqlDump .= 'NULL';
                    }
                    if ($i < ($numFields - 1)) {
                        $sqlDump .= ',';
                    }
                }
                $rowCount++;
                if ($rowCount < mysqli_num_rows($rows)) {
                    $sqlDump .= "),\n";
                }
                else {
                    $sqlDump .= ");\n\n";
                }
            }
        }
    }

    $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Save to file
    if (file_put_contents($backupFile, $sqlDump)) {
        $auditLogger->logAction($_SESSION['user_id'], 'backup_create', 'database', null, "Created database backup: $timestamp");
        $message = "Backup created successfully: backup_$timestamp.sql";
    }
    else {
        $error = "Failed to create backup file.";
    }
}

// Handle backup deletion
if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = $backupDir . $filename;

    if (file_exists($filepath) && unlink($filepath)) {
        $auditLogger->logAction($_SESSION['user_id'], 'backup_delete', 'database', null, "Deleted backup: $filename");
        $message = "Backup deleted successfully.";
    }
    else {
        $error = "Failed to delete backup.";
    }
}

// Get list of backups
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'date' => filemtime($backupDir . $file)
            ];
        }
    }
    // Sort by date, newest first
    usort($backups, function ($a, $b) {
        return $b['date'] - $a['date'];
    });
}

include '../includes/_sidebar.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">

<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #10b981; /* Green for backups/database */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
  --indigo: #6366f1;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #065f46);
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Stat Mini Cards ── */
.stat-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2.5rem; }
.stat-mini { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);
  padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);cursor:pointer;box-shadow:var(--shadow-sm); }
.stat-mini:hover { box-shadow:var(--shadow-md);transform:translateY(-3px); border-color:var(--primary); }
.stat-mini-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:1.8rem;background:var(--surface-2);color:var(--text-secondary); }
.stat-mini-val { font-size:3rem;font-weight:800;line-height:1;color:var(--primary); }
.stat-mini-val.success { color:var(--success); }
.stat-mini-val.info { color:var(--info); }
.stat-mini-lbl { font-size:1.15rem;font-weight:600;color:var(--text-secondary);margin-top:.6rem; text-transform:uppercase; letter-spacing:0.05em; }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; background:var(--surface-2); }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-danger { background:var(--danger);color:#fff; }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }
.btn-sm { padding:0.6rem 1.2rem; font-size:1rem; }

.alert { padding:1.2rem 2rem; border-radius:var(--radius-sm); margin-bottom:2rem; display:flex; align-items:center; gap:1rem; font-weight:600; font-size:1.15rem; }
.alert-success { background:rgba(16,185,129,0.15); color:var(--success); border-left:5px solid var(--success); }
.alert-error { background:rgba(239,68,68,0.15); color:var(--danger); border-left:5px solid var(--danger); }
.alert-warning { background:rgba(245,158,11,0.15); color:var(--warning); border-left:5px solid var(--warning); }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-database"></i> System Backups</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadeIn .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-server hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-cloud-download-alt"></i></div>
            <div class="staff-hero-info">
                <h2>Database Maintenance & Backups</h2>
                <p>Generate, download, and manage system database snapshots for disaster recovery.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <form method="POST" style="margin:0;">
                    <button type="submit" name="create_backup" class="btn" style="background:#fff; color:var(--primary);" onclick="return confirm('Generate a new database snapshot? This may take a few moments.');">
                        <i class="fas fa-plus"></i> Generate New Backup
                    </button>
                </form>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Security Protocol:</strong> Backups are stored in the <code>/backups/</code> directory. It is highly recommended to download and store them in a secure, encrypted off-site location.
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--primary); background:var(--primary-light);"><i class="fas fa-database"></i></div>
                <div class="stat-mini-val"><?= count($backups) ?></div>
                <div class="stat-mini-lbl">Total Snapshots</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--info); background:rgba(59,130,246,0.15);"><i class="fas fa-hdd"></i></div>
                <div class="stat-mini-val info">
                    <?php 
                        $totalSize = array_sum(array_column($backups, 'size'));
                        echo number_format($totalSize / 1024, 1);
                    ?>
                </div>
                <div class="stat-mini-lbl">Total Size (KB)</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success); background:rgba(16,185,129,0.15);"><i class="fas fa-shield-alt"></i></div>
                <div class="stat-mini-val success">100%</div>
                <div class="stat-mini-lbl">System Integrity</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list-ul" style="color:var(--primary);"></i> Database Backup History</h3>
            </div>
            <div class="card-body" style="padding:1rem;">
                <table class="stf-table" id="backupsTable">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Creation Date</th>
                            <th>Size</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $b): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:0.8rem;">
                                    <div style="width:36px; height:36px; border-radius:8px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                                        <i class="fas fa-file-code"></i>
                                    </div>
                                    <strong style="font-size:1.15rem; color:var(--text-primary);"><?= htmlspecialchars($b['name']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--text-primary);"><?= date('M j, Y', $b['date']) ?></div>
                                <div style="font-size:0.9rem; color:var(--text-muted);"><i class="far fa-clock"></i> <?= date('g:i A', $b['date']) ?></div>
                            </td>
                            <td>
                                <span style="background:var(--surface-2); padding:0.4rem 0.8rem; border-radius:12px; font-weight:700; color:var(--text-secondary); border:1px solid var(--border);">
                                    <?= number_format($b['size'] / 1024, 2) ?> KB
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex; justify-content:flex-end; gap:0.8rem;">
                                    <a href="../../backups/<?= urlencode($b['name']) ?>" class="btn btn-primary btn-sm" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <a href="?delete=<?= urlencode($b['name']) ?>" class="btn btn-ghost btn-sm" style="color:var(--danger);" onclick="return confirm('Permanently delete this backup? This action is irreversible.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle" style="color:var(--info);"></i> Recovery Guidelines</h3>
            </div>
            <div class="card-body">
                <ul style="line-height:2.2; font-size:1.15rem; color:var(--text-secondary); list-style:none; padding:0;">
                    <li><i class="fas fa-check-circle" style="color:var(--success); margin-right:10px;"></i> All backups include full schema structures and table data.</li>
                    <li><i class="fas fa-check-circle" style="color:var(--success); margin-right:10px;"></i> Backups are generated in standard SQL format, compatible with MySQL 8.0+.</li>
                    <li><i class="fas fa-check-circle" style="color:var(--success); margin-right:10px;"></i> It is recommended to perform a manual backup before any major system configuration changes.</li>
                    <li><i class="fas fa-check-circle" style="color:var(--success); margin-right:10px;"></i> Restore backups using <code>mysql -u root -p rmu_medical < backup.sql</code> or via PHPMyAdmin.</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<script>
    $(document).ready(function() {
        if ($('#backupsTable').length) {
            $('#backupsTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[1, 'desc']],
                language: { search: "", searchPlaceholder: "Search backups..." }
            });
            $('.dataTables_filter input').addClass('form-control').css({'width':'250px','display':'inline-block', 'margin-left':'10px'});
        }
    });

    const themeIcon = document.getElementById('themeIcon');
    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const html = document.documentElement;
        const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
        html.setAttribute('data-theme', t);
        localStorage.setItem('rmu_theme', t);
        if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
