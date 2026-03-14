<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'doctors';
$page_title  = 'Update Doctor Profile';

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['D_ID']) ? intval($_GET['D_ID']) : 0);
if ($id <= 0) {
    // Also try checking POST
    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_POST['D_ID']) ? intval($_POST['D_ID']) : 0);
}

if ($id <= 0) {
    header('Location: /RMU-Medical-Management-System/php/Doctor/doctor.php?error=' . urlencode('Invalid doctor ID'));
    exit;
}

// Fetch doctor record
$stmt = mysqli_prepare($conn, "SELECT d.*, u.name as full_name, u.gender, u.email, u.phone FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doctor_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle fallback if they meant user_id or legacy schema didn't link correctly
if (!$doctor_data) {
    // Some legacy systems linked D_ID to doctors.id directly without join if users was separate. Let's try raw lookup.
    $stmt2 = mysqli_prepare($conn, "SELECT * FROM doctors WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    $doctor_data = mysqli_fetch_assoc($res2);
    mysqli_stmt_close($stmt2);
    
    // In legacy update-doctor.php, 'full_name' and 'gender' were columns directly on the doctors table.
    // If we're midway through migration, those might exist.
}

if (!$doctor_data) {
    header('Location: /RMU-Medical-Management-System/php/Doctor/doctor.php?error=' . urlencode('Doctor record not found'));
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $name = trim($_POST['full_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $avail_days = trim($_POST['available_days'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');

    // Check if the old schema fields exist on `doctors` table, or if we must update `users`.
    // The legacy code updated `doctors` table with `full_name`, `gender`, `available_days`, and `specialization`.
    // We will do what the legacy code did to maintain DB compatibility, or update users if there's a join.
    
    // Prepare column checks against doctors table
    $has_full_name = array_key_exists('full_name', $doctor_data);
    $has_gender = array_key_exists('gender', $doctor_data);
    
    if (isset($doctor_data['user_id']) && $doctor_data['user_id'] > 0) {
        // New schema: update users table for name/gender, doctors table for the rest.
        $stmt_u = mysqli_prepare($conn, "UPDATE users SET name=?, gender=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_u, "ssi", $name, $gender, $doctor_data['user_id']);
        mysqli_stmt_execute($stmt_u);
        mysqli_stmt_close($stmt_u);
        
        $stmt_d = mysqli_prepare($conn, "UPDATE doctors SET available_days=?, specialization=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_d, "ssi", $avail_days, $specialization, $id);
        if(mysqli_stmt_execute($stmt_d)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /RMU-Medical-Management-System/php/Doctor/doctor.php?success=' . urlencode('Doctor updated successfully'));
            exit;
        } else {
            $error_message = "Database error updating doctor record.";
        }
    } else {
        // Legacy schema: all in doctors table
        $sql = "UPDATE doctors SET full_name=?, gender=?, available_days=?, specialization=? WHERE id=?";
        $stmt_u = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt_u, "ssssi", $name, $gender, $avail_days, $specialization, $id);
        if(mysqli_stmt_execute($stmt_u)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /RMU-Medical-Management-System/php/Doctor/doctor.php?success=' . urlencode('Doctor updated successfully'));
            exit;
        } else {
            $error_message = "Database error updating doctor record.";
        }
    }
}

// Extract current values for the form securely
$current_name = isset($doctor_data['name']) ? $doctor_data['name'] : (isset($doctor_data['full_name']) ? $doctor_data['full_name'] : '');
$current_gender = $doctor_data['gender'] ?? '';
$current_specialization = $doctor_data['specialization'] ?? '';
$current_avail = $doctor_data['available_days'] ?? '';
$doc_display_id = $doctor_data['doctor_id'] ?? ('DOC-'.$id);

$specializations = ['General Medicine', 'Emergency Medicine', 'Cardiology', 'Dermatology', 'Gynaecology',
                    'Internal Medicine', 'Neurology', 'Ophthalmology', 'Orthopaedics', 'Paediatrics',
                    'Psychiatry', 'Radiology', 'Sports Medicine', 'Surgery', 'Urology', 'Other'];
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-md"></i> Update Doctor</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Update Doctor Profile</h1>
                <p>Dashboard &rarr; Doctor Management &rarr; Update Doctor</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/Doctor/doctor.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Doctors
            </a>
        </div>

        <?php if ($error_message): ?>
        <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error_message); ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="updateDoctorForm" novalidate onsubmit="return handleFormSubmit(this);">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div style="display:flex;flex-wrap:wrap;gap:2rem;">
                <div style="flex:2;min-width:300px;">
                    <!-- Personal Info -->
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-id-card" style="color:#fff;"></i> Doctor Information</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">System ID</label>
                                    <input type="text" class="adm-search-input" value="<?php echo htmlspecialchars($doc_display_id); ?>" disabled>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Full Name <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="full_name" class="adm-search-input" required
                                           placeholder="Dr. First Last"
                                           value="<?php echo htmlspecialchars($current_name); ?>">
                                </div>
                            </div>
                            
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Gender <span style="color:var(--danger);">*</span></label>
                                    <select name="gender" class="adm-search-input" required>
                                        <option value="">— Select —</option>
                                        <option value="Male" <?php echo ($current_gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($current_gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Others" <?php echo ($current_gender === 'Others' || $current_gender === 'Other') ? 'selected' : ''; ?>>Others</option>
                                    </select>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Specialization <span style="color:var(--danger);">*</span></label>
                                    <select name="specialization" class="adm-search-input" required>
                                        <option value="">— Select Specialization —</option>
                                        <?php foreach ($specializations as $s): ?>
                                        <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($current_specialization === $s) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
                                        <?php endforeach; ?>
                                        <?php if (!in_array($current_specialization, $specializations) && !empty($current_specialization)): ?>
                                        <option value="<?php echo htmlspecialchars($current_specialization); ?>" selected><?php echo htmlspecialchars($current_specialization); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Available Days</label>
                                <div style="display:flex;flex-wrap:wrap;gap:.8rem;">
                                    <?php
                                    foreach ($days as $d):
                                        $checked = str_contains($current_avail, $d) ? 'checked' : '';
                                    ?>
                                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;background:var(--surface-2);padding:.6rem 1.2rem;border:1px solid var(--border);border-radius:6px;font-size:1.3rem;">
                                        <input type="checkbox" name="available_day[]" value="<?php echo htmlspecialchars($d); ?>" <?php echo $checked; ?> style="accent-color:var(--primary);">
                                        <?php echo htmlspecialchars($d); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="available_days" id="availableDaysHidden" value="<?php echo htmlspecialchars($current_avail); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-check-circle" style="color:#fff;"></i> Save Changes</h3>
                        </div>
                        <div class="adm-card-body">
                            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;margin-bottom:1rem;">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                            <a href="/RMU-Medical-Management-System/php/Doctor/doctor.php" class="adm-btn adm-btn-ghost" style="width:100%;justify-content:center;">
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