<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'staff';
$page_title  = 'Update Staff';

// Get Staff ID
$id = isset($_GET['id']) ? $_GET['id'] : (isset($_GET['S_ID']) ? $_GET['S_ID'] : '');
if (!$id && isset($_POST['id'])) $id = $_POST['id'];
if (!$id && isset($_POST['S_ID'])) $id = $_POST['S_ID'];

if (!$id) {
    header('Location: /RMU-Medical-Management-System/php/staff/staff.php?error=' . urlencode('Invalid staff ID'));
    exit;
}

// Fetch staff
$stmt = mysqli_prepare($conn, "SELECT * FROM staff WHERE S_ID = ?");
mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
$staff_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$staff_data) {
    header('Location: /RMU-Medical-Management-System/php/staff/staff.php?error=' . urlencode('Staff record not found'));
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
        die("CSRF token validation failed.");
    }

    $name       = trim($_POST['full_name'] ?? '');
    $gender     = $_POST['gender'] ?? '';
    // $email and $role could be added here if we join to users table, but the original update only modified staff.
    // To safely respect the layout while not risking bad UPDATE to users table mapping, we'll just update staff.
    $avail_days = trim($_POST['available_days'] ?? '');

    if (!$name || !$gender) {
        $error = 'Full Name and Gender are required.';
    } else {
        $stmt_s = mysqli_prepare($conn, "UPDATE staff SET S_Name=?, Gender=?, Work_Day=? WHERE S_ID=?");
        mysqli_stmt_bind_param($stmt_s, "ssss", $name, $gender, $avail_days, $id);
        if (mysqli_stmt_execute($stmt_s)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /RMU-Medical-Management-System/php/staff/staff.php?success=' . urlencode('Staff updated successfully'));
            exit();
        } else {
            $error = 'Database error updating staff record.';
        }
        mysqli_stmt_close($stmt_s);
    }
}

$cur_code = $staff_data['S_ID'] ?? '';
$cur_name = $staff_data['S_Name'] ?? '';
$cur_gender = $staff_data['Gender'] ?? '';
$cur_work_day = $staff_data['Work_Day'] ?? '';

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-edit"></i> Update Staff</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Update Staff Member</h1>
                <p>Dashboard &rarr; Staff Management &rarr; Update Staff</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/staff/staff.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Staff Directory
            </a>
        </div>

        <?php if ($error): ?>
        <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="updateStaffForm" novalidate onsubmit="return handleFormSubmit(this);">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            
            <div style="display:flex;flex-wrap:wrap;gap:2rem;">
                <!-- Left Column -->
                <div style="flex:2;min-width:300px;">
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-id-badge" style="color:#fff;"></i> Personal Information</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Staff ID</label>
                                    <input type="text" class="adm-search-input" value="<?php echo htmlspecialchars($cur_code); ?>" disabled>
                                </div>
                            </div>
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Full Name <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="full_name" class="adm-search-input" required
                                           value="<?php echo htmlspecialchars($cur_name); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Gender <span style="color:var(--danger);">*</span></label>
                                    <select name="gender" class="adm-search-input" required>
                                        <option value="">— Select —</option>
                                        <option value="Male" <?php echo ($cur_gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($cur_gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Others" <?php echo ($cur_gender === 'Others' || $cur_gender === 'Other') ? 'selected' : ''; ?>>Others</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-briefcase" style="color:#fff;"></i> Employment Details</h3>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Work Days</label>
                                <div style="display:flex;flex-wrap:wrap;gap:.8rem;">
                                    <?php
                                    foreach ($days as $d):
                                        $checked = str_contains($cur_work_day, $d) ? 'checked' : '';
                                    ?>
                                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;background:var(--surface-2);padding:.6rem 1.2rem;border:1px solid var(--border);border-radius:6px;font-size:1.3rem;">
                                        <input type="checkbox" name="available_day[]" value="<?php echo htmlspecialchars($d); ?>" <?php echo $checked; ?> style="accent-color:var(--primary);">
                                        <?php echo htmlspecialchars($d); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="available_days" id="availableDaysHidden" value="<?php echo htmlspecialchars($cur_work_day); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-check-circle" style="color:#fff;"></i> Action</h3>
                        </div>
                        <div class="adm-card-body">
                            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;margin-bottom:1rem;padding:1.4rem;font-size:1.5rem;">
                                <i class="fas fa-save"></i> Update Staff
                            </button>
                            <a href="/RMU-Medical-Management-System/php/staff/staff.php" class="adm-btn adm-btn-ghost" style="width:100%;justify-content:center;">
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

// Serialize checkboxes on submit
function handleFormSubmit(form) {
    if(!form.checkValidity()) return true;
    const checked = [...document.querySelectorAll('input[name="available_day[]"]:checked')].map(cb => cb.value);
    document.getElementById('availableDaysHidden').value = checked.join(', ');
    const btn = form.querySelector('button[type="submit"]');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.style.pointerEvents = 'none';
    return true;
}
</script>
</body>
</html>
