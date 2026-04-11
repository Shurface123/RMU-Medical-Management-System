<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'tests';
$page_title  = 'Update Test';

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['T_ID']) ? $_GET['T_ID'] : 0);
if (!$id && isset($_POST['id'])) $id = intval($_POST['id']);
if (!$id && isset($_POST['T_ID'])) $id = $_POST['T_ID'];

if (!$id) {
    header('Location: /RMU-Medical-Management-System/php/test/test.php?error=' . urlencode('Invalid test ID'));
    exit;
}

$is_numeric_id = is_numeric($id);

// Try new schema first 'tests'
$stmt = null;
if ($is_numeric_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM tests WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
} else {
    // If passed a code (e.g. TST-0001)
    $stmt = mysqli_prepare($conn, "SELECT * FROM tests WHERE test_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $id);
}
mysqli_stmt_execute($stmt);
$test_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Fallback to legacy 'test' table
if (!$test_data) {
    $stmt2 = mysqli_prepare($conn, "SELECT * FROM test WHERE T_ID = ?");
    mysqli_stmt_bind_param($stmt2, "s", $id);
    mysqli_stmt_execute($stmt2);
    $test_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
    mysqli_stmt_close($stmt2);
}

if (!$test_data) {
    header('Location: /RMU-Medical-Management-System/php/test/test.php?error=' . urlencode('Test record not found'));
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

    $test_name   = trim($_POST['test_name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cost        = (float)($_POST['cost'] ?? 0);
    $duration    = (int)($_POST['duration_minutes'] ?? 30);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    empty($description) ? $description = null : null;

    if (!$test_name) {
        $error = 'Test name is required.';
    } else {
        if (isset($test_data['id']) && $test_data['id'] > 0) {
            // New schema update
            $stmt_u = mysqli_prepare($conn, "UPDATE tests SET test_name=?, category=?, description=?, cost=?, duration_minutes=?, is_active=? WHERE id=?");
            mysqli_stmt_bind_param($stmt_u, "sssdiii", $test_name, $category, $description, $cost, $duration, $is_active, $test_data['id']);
            $success = mysqli_stmt_execute($stmt_u);
            mysqli_stmt_close($stmt_u);
        } else {
            // Legacy schema update (T_Name, T_Price)
            $tCode = $test_data['T_ID'];
            $stmt_u = mysqli_prepare($conn, "UPDATE test SET T_Name=?, T_Price=? WHERE T_ID=?");
            mysqli_stmt_bind_param($stmt_u, "sds", $test_name, $cost, $tCode);
            $success = mysqli_stmt_execute($stmt_u);
            mysqli_stmt_close($stmt_u);
        }

        if ($success) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /RMU-Medical-Management-System/php/test/test.php?success=' . urlencode('Test updated successfully'));
            exit;
        } else {
            $error = 'Database error updating test record.';
        }
    }
}

$cur_code = $test_data['test_id'] ?? ($test_data['T_ID'] ?? '');
$cur_name = isset($test_data['test_name']) ? $test_data['test_name'] : ($test_data['T_Name'] ?? '');
$cur_category = $test_data['category'] ?? '';
$cur_desc = $test_data['description'] ?? '';
$cur_cost = isset($test_data['cost']) ? $test_data['cost'] : ($test_data['T_Price'] ?? 0.00);
$cur_duration = $test_data['duration_minutes'] ?? 30;
$cur_active = isset($test_data['is_active']) ? $test_data['is_active'] : 1;

$categories = ['Haematology', 'Biochemistry', 'Microbiology', 'Immunology', 'Radiology',
                'Urinalysis', 'Parasitology', 'Serology', 'Histopathology', 'Other'];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-edit"></i> Update Diagnostic Test</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Update Diagnostic Test</h1>
                <p>Dashboard &rarr; Lab Management &rarr; Update Test</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/test/test.php" class="btn btn-ghost"><span class="btn-text">
                <i class="fas fa-arrow-left"></i> Back to Tests
            </span></a>
        </div>

        <?php if ($error): ?>
        <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <?php if(!isset($test_data['id'])): ?>
        <div class="adm-alert adm-alert-info" style="margin-bottom:1.5rem;">
            <i class="fas fa-info-circle"></i>
            <div>You are editing a <strong>legacy record</strong>. Only Name and Cost changes are supported for this record until safely migrated to the <code>tests</code> catalogue structure.</div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate onsubmit="return handleFormSubmit(this);">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            
            <div style="display:flex;flex-wrap:wrap;gap:2rem;">
                <!-- Left Column -->
                <div style="flex:2;min-width:300px;">
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-flask" style="color:#fff;"></i> Test Information</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Test ID</label>
                                    <input type="text" class="adm-search-input" value="<?php echo htmlspecialchars($cur_code); ?>" disabled>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Test Name <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="test_name" class="adm-search-input" required
                                           value="<?php echo htmlspecialchars($cur_name); ?>">
                                </div>
                            </div>

                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Category</label>
                                    <select name="category" class="adm-search-input" <?php echo !isset($test_data['id']) ? 'disabled' : ''; ?>>
                                        <option value="">— Select Category —</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($cur_category === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                        <?php endforeach; ?>
                                        <?php if (!in_array($cur_category, $categories) && $cur_category): ?>
                                        <option value="<?php echo htmlspecialchars($cur_category); ?>" selected><?php echo htmlspecialchars($cur_category); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Estimated Duration (min)</label>
                                    <input type="number" name="duration_minutes" class="adm-search-input" min="5" max="480" <?php echo !isset($test_data['id']) ? 'disabled' : ''; ?>
                                           value="<?php echo htmlspecialchars($cur_duration); ?>">
                                </div>
                            </div>
                            
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Cost (GH₵) <span style="color:var(--danger);">*</span></label>
                                <input type="number" name="cost" class="adm-search-input" min="0" step="0.01" required
                                       value="<?php echo htmlspecialchars($cur_cost); ?>">
                            </div>

                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Description / Instructions</label>
                                <textarea name="description" class="adm-search-input" rows="4" style="resize:vertical;" <?php echo !isset($test_data['id']) ? 'disabled' : ''; ?>><?php echo htmlspecialchars($cur_desc); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-toggle-on" style="color:#fff;"></i> Status</h3>
                        </div>
                        <div class="adm-card-body">
                            <label style="display:flex;align-items:center;gap:1rem;cursor:pointer;margin-bottom:2rem;">
                                <input type="checkbox" name="is_active" <?php echo $cur_active ? 'checked' : ''; ?> <?php echo !isset($test_data['id']) ? 'disabled' : ''; ?> style="width:20px;height:20px;accent-color:var(--primary);">
                                <span style="font-size:1.4rem;font-weight:600;color:var(--text-primary);">Test is Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-check-circle" style="color:#fff;"></i> Save Changes</h3>
                        </div>
                        <div class="adm-card-body">
                            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-bottom:1rem;padding:1.4rem;font-size:1.5rem;"><span class="btn-text">
                                <i class="fas fa-save"></i> Update Test
                            </span></button>
                            <a href="/RMU-Medical-Management-System/php/test/test.php"
                               class="btn btn-ghost" style="width:100%;justify-content:center;"><span class="btn-text">
                                Cancel
                            </span></a>
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
