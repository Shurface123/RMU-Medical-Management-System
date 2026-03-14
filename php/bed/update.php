<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'bed';
$page_title  = 'Update Bed';

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if ($id <= 0) {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?error=' . urlencode('Invalid bed ID'));
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    $bed_number = trim($_POST['bed_number'] ?? '');
    $ward       = trim($_POST['ward'] ?? '');
    $bed_type   = $_POST['bed_type'] ?? 'Standard';
    $daily_rate = (float)($_POST['daily_rate'] ?? 0);
    $status     = $_POST['status'] ?? 'Available';
    $notes      = trim($_POST['notes'] ?? '');

    if (!$bed_number || !$ward) {
        $error = 'Bed Number and Ward are required.';
    } else {
        $stmt_u = mysqli_prepare($conn, "UPDATE beds SET bed_number=?, ward=?, bed_type=?, daily_rate=?, status=?, notes=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_u, "sssdssi", $bed_number, $ward, $bed_type, $daily_rate, $status, $notes, $id);
        if (mysqli_stmt_execute($stmt_u)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /RMU-Medical-Management-System/php/bed/bed.php?success=' . urlencode('Bed updated successfully'));
            exit;
        } else {
            $error = 'Database error updating bed record.';
        }
        mysqli_stmt_close($stmt_u);
    }
}

$stmt = mysqli_prepare($conn, "SELECT * FROM beds WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$bed = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$bed) {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?error=' . urlencode('Bed record not found'));
    exit;
}

$wards     = ['General Ward','Male Ward','Female Ward','Paediatric Ward','Maternity Ward',
              'Emergency Ward','ICU','HDU','Isolation Ward','Observation Ward'];
$bed_types = ['Standard','Semi-Private','Private','ICU','Recovery','Bariatric'];
$statuses  = ['Available','Occupied','Maintenance','Reserved'];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-edit"></i> Update Bed</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Update Bed Matrix</h1>
                <p>Dashboard &rarr; Bed Management &rarr; Update Bed</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/bed/bed.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Bed Matrix
            </a>
        </div>

        <?php if ($error): ?>
        <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate onsubmit="return handleFormSubmit(this);">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            
            <div style="display:flex;flex-wrap:wrap;gap:2rem;">
                <!-- Left Column -->
                <div style="flex:2;min-width:300px;">
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-bed" style="color:#fff;"></i> Bed Details</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Bed Number <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="bed_number" class="adm-search-input" required
                                           value="<?php echo htmlspecialchars($bed['bed_number']); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Ward <span style="color:var(--danger);">*</span></label>
                                    <select name="ward" class="adm-search-input" required>
                                        <?php foreach ($wards as $w): ?>
                                            <option value="<?php echo htmlspecialchars($w); ?>" <?php echo ($bed['ward'] === $w) ? 'selected' : ''; ?>><?php echo htmlspecialchars($w); ?></option>
                                        <?php endforeach; ?>
                                        <?php if (!in_array($bed['ward'], $wards) && $bed['ward']): ?>
                                            <option value="<?php echo htmlspecialchars($bed['ward']); ?>" selected><?php echo htmlspecialchars($bed['ward']); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Bed Type <span style="color:var(--danger);">*</span></label>
                                    <select name="bed_type" class="adm-search-input" required>
                                        <?php foreach ($bed_types as $bt): ?>
                                            <option value="<?php echo htmlspecialchars($bt); ?>" <?php echo ($bed['bed_type'] === $bt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($bt); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Daily Rate (GH₵)</label>
                                    <input type="number" name="daily_rate" class="adm-search-input" min="0" step="0.50"
                                           value="<?php echo htmlspecialchars($bed['daily_rate']); ?>">
                                </div>
                            </div>
                            
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Notes</label>
                                <textarea name="notes" class="adm-search-input" rows="3" style="resize:vertical;"><?php echo htmlspecialchars($bed['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-info-circle" style="color:#fff;"></i> Current Status</h3>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Bed Availability</label>
                                <select name="status" class="adm-search-input" required>
                                    <?php foreach ($statuses as $s): ?>
                                        <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($bed['status'] === $s) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-check-circle" style="color:#fff;"></i> Save Changes</h3>
                        </div>
                        <div class="adm-card-body">
                            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;margin-bottom:1rem;padding:1.4rem;font-size:1.5rem;">
                                <i class="fas fa-save"></i> Update Bed
                            </button>
                            <a href="/RMU-Medical-Management-System/php/bed/bed.php"
                               class="adm-btn adm-btn-ghost" style="width:100%;justify-content:center;">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});

function handleFormSubmit(form) {
    if(!form.checkValidity()) return true;
    const btn = form.querySelector('button[type="submit"]');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.style.pointerEvents = 'none';
    return true;
}
</script>
</body>
</html>