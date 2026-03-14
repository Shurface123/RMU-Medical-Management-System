<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'staff';
$page_title  = 'Add Staff';

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
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $gender     = $_POST['gender'] ?? '';
    $role       = $_POST['staff_role'] ?? 'staff';
    $avail_days = trim($_POST['available_days'] ?? '');

    if (!$name || !$gender) {
        $error = 'Full Name and Gender are required.';
    } else {
        // Build staff_id (S_ID)
        // Check legacy schema maximum auto-increment or id rules
        $last_res = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING_INDEX(S_ID, '-', -1) AS UNSIGNED)) FROM staff WHERE S_ID LIKE 'STF-%'");
        $last_val = 0;
        if ($last_res && $row = mysqli_fetch_row($last_res)) {
            $last_val = (int)$row[0];
        } else {
            // fallback if it wasn't strictly formatted previously
            $last_val = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff"))[0] ?? 0;
        }
        $staff_id = 'STF-' . str_pad($last_val + 1, 4, '0', STR_PAD_LEFT);

        // Optional: user table insertion
        $user_id = null;
        if ($email) {
            $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
            mysqli_stmt_bind_param($stmt_check, "s", $email);
            mysqli_stmt_execute($stmt_check);
            $res = mysqli_stmt_get_result($stmt_check);
            if ($row = mysqli_fetch_assoc($res)) {
                $user_id = $row['id']; 
            }
            mysqli_stmt_close($stmt_check);

            if (!$user_id) {
                $default_pass = password_hash('rmu@123', PASSWORD_DEFAULT);
                $is_act = 1;
                $stmt_user = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, gender, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_user, "ssssssi", $name, $email, $phone, $gender, $default_pass, $role, $is_act);
                if (mysqli_stmt_execute($stmt_user)) {
                    $user_id = mysqli_insert_id($conn);
                }
                mysqli_stmt_close($stmt_user);
            }
        }

        // Insert into staff table (S_ID, S_Name, Gender, Work_Day)
        $stmt_s = mysqli_prepare($conn, "INSERT INTO staff (S_ID, S_Name, Gender, Work_Day) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_s, "ssss", $staff_id, $name, $gender, $avail_days);
        if (mysqli_stmt_execute($stmt_s)) {
            // Optional: if the staff table is later updated with user_id or role, we'd add it here, 
            // but for now we follow the exact legacy schema columns for staff while still granting login access.
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /RMU-Medical-Management-System/php/staff/staff.php?success=' . urlencode('Staff registered successfully'));
            exit();
        } else {
            $error = 'Database error adding staff record.';
        }
        mysqli_stmt_close($stmt_s);
    }
}

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$roles = [
    'general_staff' => 'General Staff',
    'ambulance_driver' => 'Ambulance Driver',
    'cleaner' => 'Cleaner',
    'laundry_staff' => 'Laundry Staff',
    'maintenance' => 'Maintenance',
    'security' => 'Security',
    'kitchen_staff' => 'Kitchen Staff'
];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-tie"></i> Add Staff</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Add New Staff Member</h1>
                <p>Dashboard &rarr; Staff Management &rarr; Add Staff</p>
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

        <div class="adm-alert adm-alert-info" style="margin-bottom:1.5rem;">
            <i class="fas fa-info-circle"></i>
            <div>If an email is provided, a staff login account will be generated with default password: <strong>rmu@123</strong></div>
        </div>

        <form method="POST" action="" id="addStaffForm" novalidate onsubmit="return handleFormSubmit(this);">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div style="display:flex;flex-wrap:wrap;gap:2rem;">
                <!-- Left Column -->
                <div style="flex:2;min-width:300px;">
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-id-badge" style="color:#fff;"></i> Personal Information</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Full Name <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="full_name" class="adm-search-input" required
                                           placeholder="e.g. John Doe"
                                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Gender <span style="color:var(--danger);">*</span></label>
                                    <select name="gender" class="adm-search-input" required>
                                        <option value="">— Select —</option>
                                        <option value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Others" <?php echo (($_POST['gender'] ?? '') === 'Others') ? 'selected' : ''; ?>>Others</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Phone Number</label>
                                    <input type="tel" name="phone" class="adm-search-input"
                                           placeholder="0XXXXXXXXX"
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Email Address</label>
                                    <input type="email" name="email" class="adm-search-input"
                                           placeholder="staff@rmu.edu.gh"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-briefcase" style="color:#fff;"></i> Employment Details</h3>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">System Role / Type <span style="color:var(--danger);">*</span></label>
                                <select name="staff_role" class="adm-search-input" required>
                                    <?php foreach ($roles as $key => $val): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (($_POST['staff_role'] ?? 'general_staff') === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($val); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Work Days</label>
                                <div style="display:flex;flex-wrap:wrap;gap:.8rem;">
                                    <?php
                                    $avail_post = $_POST['available_days'] ?? '';
                                    foreach ($days as $d):
                                        $checked = str_contains($avail_post, $d) ? 'checked' : '';
                                    ?>
                                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;background:var(--surface-2);padding:.6rem 1.2rem;border:1px solid var(--border);border-radius:6px;font-size:1.3rem;">
                                        <input type="checkbox" name="available_day[]" value="<?php echo htmlspecialchars($d); ?>" <?php echo $checked; ?> style="accent-color:var(--primary);">
                                        <?php echo htmlspecialchars($d); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="available_days" id="availableDaysHidden" value="<?php echo htmlspecialchars($avail_post); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-check-circle" style="color:#fff;"></i> Action</h3>
                        </div>
                        <div class="adm-card-body">
                            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;margin-bottom:1rem;padding:1.4rem;font-size:1.5rem;">
                                <i class="fas fa-save"></i> Register Staff
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