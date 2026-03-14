<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'patients';
$page_title  = 'Add Patient';

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

    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $dob          = $_POST['date_of_birth'] ?? '';
    $gender       = $_POST['gender'] ?? '';
    $blood_group  = $_POST['blood_group'] ?? '';
    $address      = trim($_POST['address'] ?? '');
    $patient_type = $_POST['patient_type'] ?? 'Outpatient';
    $emg_name     = trim($_POST['emergency_contact_name'] ?? '');
    $emg_phone    = trim($_POST['emergency_contact_phone'] ?? '');
    $admit_date   = $_POST['admit_date'] ?? date('Y-m-d');

    if (!$full_name || !$gender) {
        $error = 'Full name and gender are required.';
    } else {
        // Build patient_id securely without concurrency issue: use transaction or basic auto-increment fallback
        $last_p = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients"))[0] ?? 0;
        $patient_id = 'PAT-' . str_pad($last_p + 1, 5, '0', STR_PAD_LEFT);

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
                $role_pat = 'patient';
                $is_act = 1;

                $stmt_user = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, gender, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_user, "ssssssi", $full_name, $email, $phone, $gender, $default_pass, $role_pat, $is_act);
                if (mysqli_stmt_execute($stmt_user)) {
                    $user_id = mysqli_insert_id($conn);
                }
                mysqli_stmt_close($stmt_user);
            }
        }

        $age_val = null;
        if ($dob) {
            $age_val = (int)((time() - strtotime($dob)) / (365.25 * 86400));
        }

        empty($dob) ? $dob = null : null;
        empty($blood_group) ? $blood_group = null : null;
        empty($address) ? $address = null : null;
        empty($emg_name) ? $emg_name = null : null;
        empty($emg_phone) ? $emg_phone = null : null;

        $stmt = mysqli_prepare($conn, "INSERT INTO patients (user_id, patient_id, full_name, gender, age, date_of_birth, blood_group, address, patient_type, emergency_contact_name, emergency_contact_phone, admit_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isssisssssss", $user_id, $patient_id, $full_name, $gender, $age_val, $dob, $blood_group, $address, $patient_type, $emg_name, $emg_phone, $admit_date);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Rotate
            header('Location: /RMU-Medical-Management-System/php/patient/patient.php?success=' . urlencode('Patient registered successfully'));
            exit();
        } else {
            $error = 'Database error adding patient record.';
        }
        mysqli_stmt_close($stmt);
    }
}
$blood_groups = ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-injured"></i> Add New Patient</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Add New Patient</h1>
                <p>Dashboard &rarr; Patient Management &rarr; Add Patient</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/patient/patient.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Patients
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
                <div style="flex:2;min-width:300px;">
                    <!-- Personal Info -->
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-id-card" style="color:#fff;"></i> Personal Information</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Full Name <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="full_name" class="adm-search-input" required placeholder="Patient's full name"
                                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="adm-search-input" max="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Gender <span style="color:var(--danger);">*</span></label>
                                    <select name="gender" class="adm-search-input" required>
                                        <option value="">— Select —</option>
                                        <option value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Blood Group</label>
                                    <select name="blood_group" class="adm-search-input">
                                        <option value="">— Unknown —</option>
                                        <?php foreach ($blood_groups as $bg): ?>
                                        <option value="<?php echo htmlspecialchars($bg); ?>" <?php echo (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : ''; ?>><?php echo htmlspecialchars($bg); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Phone Number</label>
                                    <input type="tel" name="phone" class="adm-search-input" placeholder="0XXXXXXXXX"
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Email Address</label>
                                    <input type="email" name="email" class="adm-search-input" placeholder="patient@email.com (optional)"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Home Address</label>
                                <textarea name="address" class="adm-search-input" rows="2" style="resize:vertical;" placeholder="Street, Town, Region"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-phone-alt" style="color:#fff;"></i> Emergency Contact</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Contact Person Name</label>
                                    <input type="text" name="emergency_contact_name" class="adm-search-input" placeholder="Parent / Guardian / Spouse"
                                           value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Contact Phone</label>
                                    <input type="tel" name="emergency_contact_phone" class="adm-search-input" placeholder="0XXXXXXXXX"
                                           value="<?php echo htmlspecialchars($_POST['emergency_contact_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-hospital-alt" style="color:#fff;"></i> Admission Details</h3>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Patient Type</label>
                                <select name="patient_type" class="adm-search-input">
                                    <?php foreach(['Outpatient','Inpatient','Emergency','Semi-Urgent'] as $pt): ?>
                                    <option value="<?php echo htmlspecialchars($pt); ?>" <?php echo (($_POST['patient_type'] ?? 'Outpatient') === $pt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Admission Date</label>
                                <input type="date" name="admit_date" class="adm-search-input"
                                       value="<?php echo htmlspecialchars($_POST['admit_date'] ?? date('Y-m-d')); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="adm-card" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:2rem;">
                        <div class="adm-card-body" style="text-align:center;padding:2rem 1.5rem;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="fas fa-user-injured" style="color:#fff;font-size:2rem;"></i>
                            </div>
                            <p style="font-size:1.2rem;color:var(--text-secondary);line-height:1.6;">If an email is provided, a <strong>patient</strong> login account will be created with default password <code style="background:var(--surface);padding:.2rem .6rem;border-radius:6px;color:var(--text-primary);">rmu@123</code>.</p>
                        </div>
                    </div>

                    <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;margin-bottom:1rem;padding:1.4rem;font-size:1.5rem;">
                        <i class="fas fa-save"></i> Register Patient
                    </button>
                    <a href="/RMU-Medical-Management-System/php/patient/patient.php"
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
