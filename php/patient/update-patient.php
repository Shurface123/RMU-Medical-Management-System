<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'patients';
$page_title  = 'Update Patient Record';

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['P_ID']) ? intval($_GET['P_ID']) : 0);
if ($id <= 0) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_POST['P_ID']) ? intval($_POST['P_ID']) : 0);
}

if ($id <= 0) {
    header('Location: /RMU-Medical-Management-System/php/patient/patient.php?error=' . urlencode('Invalid patient ID'));
    exit;
}

// Fetch patient record
$stmt = mysqli_prepare($conn, "SELECT p.*, u.email, u.phone FROM patients p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$patient_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$patient_data) {
    header('Location: /RMU-Medical-Management-System/php/patient/patient.php?error=' . urlencode('Patient record not found'));
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

    $full_name = trim($_POST['full_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = trim($_POST['date_of_birth'] ?? '');
    $blood_group = $_POST['blood_group'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $emg_name = trim($_POST['emergency_contact_name'] ?? '');
    $emg_phone = trim($_POST['emergency_contact_phone'] ?? '');
    $patient_type = $_POST['patient_type'] ?? 'Outpatient';
    $admit_date = trim($_POST['admit_date'] ?? '');

    if (!$full_name || !$gender) {
        $error_message = 'Full name and gender are required.';
    } else {
        $age_val = null;
        if ($dob) {
            $age_val = (int)((time() - strtotime($dob)) / (365.25 * 86400));
        }

        empty($dob) ? $dob = null : null;
        empty($blood_group) ? $blood_group = null : null;
        empty($address) ? $address = null : null;
        empty($emg_name) ? $emg_name = null : null;
        empty($emg_phone) ? $emg_phone = null : null;
        empty($admit_date) ? $admit_date = null : null;

        // If patient has a user ID, update user table too (email/phone)
        if ($patient_data['user_id']) {
            $stmt_u = mysqli_prepare($conn, "UPDATE users SET name=?, gender=?, email=?, phone=? WHERE id=?");
            mysqli_stmt_bind_param($stmt_u, "ssssi", $full_name, $gender, $email, $phone, $patient_data['user_id']);
            mysqli_stmt_execute($stmt_u);
            mysqli_stmt_close($stmt_u);
        }

        $stmt_p = mysqli_prepare($conn, "UPDATE patients SET full_name=?, gender=?, age=?, date_of_birth=?, blood_group=?, address=?, patient_type=?, emergency_contact_name=?, emergency_contact_phone=?, admit_date=? WHERE id=?");
        
        // Age is int, ID is int, others are strings/dates. types: s s i s s s s s s s i -> "ssisssssssi"
        mysqli_stmt_bind_param($stmt_p, "ssisssssssi", $full_name, $gender, $age_val, $dob, $blood_group, $address, $patient_type, $emg_name, $emg_phone, $admit_date, $id);
        
        if (mysqli_stmt_execute($stmt_p)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /RMU-Medical-Management-System/php/patient/patient.php?success=' . urlencode('Patient updated successfully'));
            exit;
        } else {
            $error_message = 'Database error updating patient record.';
        }
        mysqli_stmt_close($stmt_p);
    }
}

// Fallback values for legacy data
$cur_name = isset($patient_data['full_name']) ? $patient_data['full_name'] : (isset($patient_data['P_Name']) ? $patient_data['P_Name'] : (isset($patient_data['name']) ? $patient_data['name'] : ''));
$cur_gender = $patient_data['gender'] ?? '';
$cur_dob = $patient_data['date_of_birth'] ?? '';
$cur_blood = $patient_data['blood_group'] ?? '';
$cur_phone = $patient_data['phone'] ?? '';
$cur_email = $patient_data['email'] ?? '';
$cur_addr = $patient_data['address'] ?? '';
$cur_emg_name = $patient_data['emergency_contact_name'] ?? '';
$cur_emg_phone = $patient_data['emergency_contact_phone'] ?? '';
$cur_type = isset($patient_data['patient_type']) ? $patient_data['patient_type'] : (isset($patient_data['type']) ? $patient_data['type'] : 'Outpatient');
$cur_admit = isset($patient_data['admit_date']) ? $patient_data['admit_date'] : (isset($patient_data['admission_date']) ? $patient_data['admission_date'] : '');
$cur_pat_id = $patient_data['patient_id'] ?? ('PAT-'.$id);

$blood_groups = ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-injured"></i> Update Patient</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Update Patient Record</h1>
                <p>Dashboard &rarr; Patient Management &rarr; Update Patient</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/patient/patient.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Patients
            </a>
        </div>

        <?php if ($error_message): ?>
        <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error_message); ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate onsubmit="return handleFormSubmit(this);">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
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
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">System ID</label>
                                    <input type="text" class="adm-search-input" value="<?php echo htmlspecialchars($cur_pat_id); ?>" disabled>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Full Name <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="full_name" class="adm-search-input" required placeholder="Patient's full name"
                                           value="<?php echo htmlspecialchars($cur_name); ?>">
                                </div>
                            </div>
                            
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="adm-search-input" max="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo htmlspecialchars($cur_dob); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Gender <span style="color:var(--danger);">*</span></label>
                                    <select name="gender" class="adm-search-input" required>
                                        <option value="">— Select —</option>
                                        <option value="Male" <?php echo ($cur_gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($cur_gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($cur_gender === 'Other' || $cur_gender === 'Others') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Blood Group</label>
                                    <select name="blood_group" class="adm-search-input">
                                        <option value="">— Unknown —</option>
                                        <?php foreach ($blood_groups as $bg): ?>
                                        <option value="<?php echo htmlspecialchars($bg); ?>" <?php echo ($cur_blood === $bg) ? 'selected' : ''; ?>><?php echo htmlspecialchars($bg); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Phone Number</label>
                                    <input type="tel" name="phone" class="adm-search-input" placeholder="0XXXXXXXXX"
                                           value="<?php echo htmlspecialchars($cur_phone); ?>">
                                </div>
                            </div>

                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Email Address</label>
                                    <input type="email" name="email" class="adm-search-input" placeholder="patient@email.com"
                                           value="<?php echo htmlspecialchars($cur_email); ?>" <?php echo $patient_data['user_id'] ? '' : 'disabled title="Cannot modify email for non-app users"'; ?>>
                                </div>
                            </div>
                            
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Home Address</label>
                                <textarea name="address" class="adm-search-input" rows="2" style="resize:vertical;" placeholder="Street, Town, Region"><?php echo htmlspecialchars($cur_addr); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-phone-alt" style="color:#fff;"></i> Emergency Contact</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Contact Person Name</label>
                                    <input type="text" name="emergency_contact_name" class="adm-search-input" placeholder="Parent / Guardian / Spouse"
                                           value="<?php echo htmlspecialchars($cur_emg_name); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Contact Phone</label>
                                    <input type="tel" name="emergency_contact_phone" class="adm-search-input" placeholder="0XXXXXXXXX"
                                           value="<?php echo htmlspecialchars($cur_emg_phone); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-hospital-alt" style="color:#fff;"></i> Admission Details</h3>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Patient Type</label>
                                <select name="patient_type" class="adm-search-input">
                                    <?php foreach(['Outpatient','Inpatient','Emergency','Semi-Urgent'] as $pt): ?>
                                    <option value="<?php echo htmlspecialchars($pt); ?>" <?php echo ($cur_type === $pt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Admission Date</label>
                                <input type="date" name="admit_date" class="adm-search-input"
                                       value="<?php echo htmlspecialchars($cur_admit); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-check-circle" style="color:#fff;"></i> Save Changes</h3>
                        </div>
                        <div class="adm-card-body">
                            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;margin-bottom:1rem;padding:1.4rem;font-size:1.5rem;">
                                <i class="fas fa-save"></i> Update Patient
                            </button>
                            <a href="/RMU-Medical-Management-System/php/patient/patient.php"
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