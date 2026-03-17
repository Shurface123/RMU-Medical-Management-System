<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'doctors';
$page_title  = 'Add Doctor';

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

    $name           = trim($_POST['name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $gender         = $_POST['gender'] ?? '';
    $specialization = trim($_POST['specialization'] ?? '');
    $experience     = (int)($_POST['experience_years'] ?? 0);
    $avail_days     = trim($_POST['available_days'] ?? '');
    $schedule       = trim($_POST['schedule_notes'] ?? '');
    $is_available   = isset($_POST['is_available']) ? 1 : 0;

    if (!$name || !$email || !$specialization) {
        $error = 'Name, Email, and Specialization are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check email uniqueness using prepared statement
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        $count_email = mysqli_stmt_num_rows($stmt_check);
        mysqli_stmt_close($stmt_check);

        if ($count_email > 0) {
            $error = 'A user with this email already exists.';
        } else {
            // Generate doctor_id
            $last_doc = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors"))[0] ?? 0;
            $doctor_id = 'DOC-' . str_pad($last_doc + 1, 4, '0', STR_PAD_LEFT);

            // Default password
            $default_pass = password_hash('rmu@123', PASSWORD_DEFAULT);
            $role_doc = 'doctor';
            $is_act = 1;

            // Insert into users table
            $stmt_user = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, gender, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_user, "ssssssi", $name, $email, $phone, $gender, $default_pass, $role_doc, $is_act);
            
            if (mysqli_stmt_execute($stmt_user)) {
                $user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt_user);

                // Insert into doctors table
                $stmt_doc = mysqli_prepare($conn, "INSERT INTO doctors (user_id, doctor_id, specialization, experience_years, available_days, schedule_notes, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_doc, "ississi", $user_id, $doctor_id, $specialization, $experience, $avail_days, $schedule, $is_available);
                
                if (mysqli_stmt_execute($stmt_doc)) {
                    mysqli_stmt_close($stmt_doc);
                    // Generate new CSRF after successful POST
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: /RMU-Medical-Management-System/php/Doctor/doctor.php?success=' . urlencode('Doctor registered successfully'));
                    exit();
                } else {
                    // Rollback user
                    mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");
                    $error = 'Doctor record error: Could not complete registration.';
                }
            } else {
                $error = 'User creation error: Could not create user account.';
            }
        }
    }
}

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
            <span class="adm-page-title"><i class="fas fa-user-md"></i> Add New Doctor</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Add New Doctor</h1>
                <p>Dashboard &rarr; Doctor Management &rarr; Add Doctor</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/Doctor/doctor.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Doctors
            </a>
        </div>

        <?php if ($error): ?>
        <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <!-- Info banner -->
        <div class="adm-alert adm-alert-info" style="margin-bottom:1.5rem;">
            <i class="fas fa-info-circle"></i>
            <div>A system account will be created for this doctor. Default password: <strong>rmu@123</strong> — they can change it after their first login.</div>
        </div>

        <form method="POST" action="" id="addDoctorForm" novalidate onsubmit="return handleFormSubmit(this);">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div style="display:flex;flex-wrap:wrap;gap:2rem;">
                <div style="flex:2;min-width:300px;">
                    <!-- Personal Info -->
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-id-card" style="color:#fff;"></i> Personal Information</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Full Name <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="name" class="adm-search-input" required
                                           placeholder="Dr. First Last"
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Email Address <span style="color:var(--danger);">*</span></label>
                                    <input type="email" name="email" class="adm-search-input" required
                                           placeholder="doctor@rmu.edu.gh"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
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
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Gender <span style="color:var(--danger);">*</span></label>
                                    <select name="gender" class="adm-search-input" required>
                                        <option value="">— Select —</option>
                                        <option value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Info -->
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-stethoscope" style="color:#fff;"></i> Professional Details</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Specialization <span style="color:var(--danger);">*</span></label>
                                    <select name="specialization" class="adm-search-input" required>
                                        <option value="">— Select Specialization —</option>
                                        <?php foreach ($specializations as $s): ?>
                                        <option value="<?php echo htmlspecialchars($s); ?>" <?php echo (($_POST['specialization'] ?? '') === $s) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Years of Experience</label>
                                    <input type="number" name="experience_years" class="adm-search-input" min="0" max="60"
                                           placeholder="0"
                                           value="<?php echo htmlspecialchars($_POST['experience_years'] ?? '0'); ?>">
                                </div>
                            </div>
                            
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Available Days</label>
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
                            
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Schedule Notes</label>
                                <textarea name="schedule_notes" class="adm-search-input" rows="3" style="resize:vertical;"
                                          placeholder="e.g. Morning shifts 8AM–12PM..."><?php echo htmlspecialchars($_POST['schedule_notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-toggle-on" style="color:#fff;"></i> Settings & Save</h3>
                        </div>
                        <div class="adm-card-body">
                            <label style="display:flex;align-items:center;gap:1rem;cursor:pointer;margin-bottom:2rem;">
                                <input type="checkbox" name="is_available" <?php echo (!isset($_POST['name']) || isset($_POST['is_available'])) ? 'checked' : ''; ?> style="width:20px;height:20px;accent-color:var(--primary);">
                                <span style="font-size:1.4rem;font-weight:600;color:var(--text-primary);">Available for appointments</span>
                            </label>

                            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;margin-bottom:1rem;">
                                <i class="fas fa-save"></i> Save Doctor
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
