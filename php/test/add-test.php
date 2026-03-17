<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'tests';
$page_title  = 'Add Test';

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $test_name   = trim($_POST['test_name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cost        = (float)($_POST['cost'] ?? 0);
    $duration    = (int)($_POST['duration_minutes'] ?? 30);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (!$test_name || !$category) {
        $error = 'Test name and category are required.';
    } else {
        // Generate test_id
        $last_t = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tests"))[0] ?? 0;
        $test_id = 'TST-' . str_pad($last_t + 1, 4, '0', STR_PAD_LEFT);

        empty($description) ? $description = null : null;

        $stmt = mysqli_prepare($conn, "INSERT INTO tests (test_id, test_name, category, description, cost, duration_minutes, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssdii", $test_id, $test_name, $category, $description, $cost, $duration, $is_active);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /RMU-Medical-Management-System/php/test/test.php?success=' . urlencode('Test added successfully'));
            exit();
        } else {
            $error = 'Database error adding testing record.';
        }
        mysqli_stmt_close($stmt);
    }
}

$categories = ['Haematology', 'Biochemistry', 'Microbiology', 'Immunology', 'Radiology',
                'Urinalysis', 'Parasitology', 'Serology', 'Histopathology', 'Other'];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-flask"></i> Add Diagnostic Test</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Add New Diagnostic Test</h1>
                <p>Dashboard &rarr; Lab Management &rarr; Add Test</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/test/test.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Tests
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
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Test Name <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="test_name" class="adm-search-input" required
                                           placeholder="e.g. Full Blood Count (FBC)"
                                           value="<?php echo htmlspecialchars($_POST['test_name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Category <span style="color:var(--danger);">*</span></label>
                                    <select name="category" class="adm-search-input" required>
                                        <option value="">— Select Category —</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (($_POST['category'] ?? '') === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Cost (GH₵) <span style="color:var(--danger);">*</span></label>
                                    <input type="number" name="cost" class="adm-search-input" min="0" step="0.01" required
                                           placeholder="0.00" value="<?php echo htmlspecialchars($_POST['cost'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Estimated Duration (min)</label>
                                    <input type="number" name="duration_minutes" class="adm-search-input" min="5" max="480"
                                           placeholder="30" value="<?php echo htmlspecialchars($_POST['duration_minutes'] ?? '30'); ?>">
                                </div>
                            </div>
                            
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Description / Instructions</label>
                                <textarea name="description" class="adm-search-input" rows="4" style="resize:vertical;"
                                          placeholder="Patient preparation, procedure description, expected results range..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
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
                                <input type="checkbox" name="is_active" <?php echo (!isset($_POST['test_name']) || isset($_POST['is_active'])) ? 'checked' : ''; ?> style="width:20px;height:20px;accent-color:var(--primary);">
                                <span style="font-size:1.4rem;font-weight:600;color:var(--text-primary);">Test is Active</span>
                            </label>
                            <p style="font-size:1.2rem;color:var(--text-muted);margin-top:1rem;line-height:1.6;">Only active tests will be available for ordering and scheduling.</p>
                        </div>
                    </div>

                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:2rem;">
                        <div class="adm-card-body" style="text-align:center;padding:2rem;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="fas fa-microscope" style="color:#fff;font-size:2rem;"></i>
                            </div>
                            <p style="font-size:1.2rem;color:var(--text-secondary);line-height:1.6;">A unique Test ID will be auto-generated (e.g. <code style="background:var(--surface);padding:.2rem .6rem;border-radius:6px;color:var(--text-primary);">TST-0001</code>).</p>
                        </div>
                    </div>

                    <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.4rem;font-size:1.5rem;margin-bottom:1rem;">
                        <i class="fas fa-save"></i> Add Test
                    </button>
                    <a href="/RMU-Medical-Management-System/php/test/test.php"
                       class="adm-btn adm-btn-ghost" style="width:100%;justify-content:center;">
                        Cancel
                    </a>
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
