<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include 'db_conn.php';

$active_page = 'patients';
$page_title  = 'Add Patient';
include '../includes/_sidebar.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $email        = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $phone        = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $dob          = mysqli_real_escape_string($conn, $_POST['date_of_birth'] ?? '');
    $gender       = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
    $blood_group  = mysqli_real_escape_string($conn, $_POST['blood_group'] ?? '');
    $address      = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
    $patient_type = mysqli_real_escape_string($conn, $_POST['patient_type'] ?? 'Outpatient');
    $emg_name     = mysqli_real_escape_string($conn, trim($_POST['emergency_contact_name'] ?? ''));
    $emg_phone    = mysqli_real_escape_string($conn, trim($_POST['emergency_contact_phone'] ?? ''));
    $admit_date   = mysqli_real_escape_string($conn, $_POST['admit_date'] ?? date('Y-m-d'));

    if (!$full_name || !$gender) {
        $error = 'Full name and gender are required.';
    } else {
        // Generate patient_id
        $last_p = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM patients"))[0] ?? 0;
        $patient_id = 'PAT-' . str_pad($last_p + 1, 5, '0', STR_PAD_LEFT);

        // Try to create user account if email provided
        $user_id = null;
        if ($email) {
            $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
            if (mysqli_num_rows($check) > 0) {
                $user_id = mysqli_fetch_assoc($check)['id'];
            } else {
                $default_pass = password_hash('rmu@123', PASSWORD_DEFAULT);
                $sql_user = "INSERT INTO users (name, email, phone, gender, password, role, is_active)
                             VALUES ('$full_name','$email','$phone','$gender','$default_pass','patient',1)";
                if (mysqli_query($conn, $sql_user)) {
                    $user_id = mysqli_insert_id($conn);
                }
            }
        }

        // Age from DOB
        $age_val = 'NULL';
        if ($dob) {
            $age_val = (int)((time() - strtotime($dob)) / (365.25 * 86400));
        }

        $user_id_val = $user_id ? $user_id : 'NULL';
        $dob_val     = $dob     ? "'$dob'" : 'NULL';
        $emg_val     = $emg_name ? "'$emg_name'" : 'NULL';
        $emg_ph_val  = $emg_phone ? "'$emg_phone'" : 'NULL';
        $bg_val      = $blood_group ? "'$blood_group'" : 'NULL';
        $addr_val    = $address ? "'$address'" : 'NULL';

        $sql = "INSERT INTO patients (user_id, patient_id, full_name, gender, age, date_of_birth, blood_group, address, patient_type, emergency_contact_name, emergency_contact_phone, admit_date)
                VALUES ($user_id_val,'$patient_id','$full_name','$gender',$age_val,$dob_val,$bg_val,$addr_val,'$patient_type',$emg_val,$emg_ph_val,'$admit_date')";
        if (mysqli_query($conn, $sql)) {
            header('Location: /RMU-Medical-Management-System/php/patient/patient.php?success=Patient+registered+successfully');
            exit();
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    }
}
$blood_groups = ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'];
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-injured" style="color:var(--primary);margin-right:.8rem;"></i>Add Patient</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1><i class="fas fa-user-plus" style="color:var(--primary);margin-right:.6rem;"></i>Register New Patient</h1>
                <p>Capture patient demographics and admission details.</p>
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

        <form method="POST" action="" novalidate>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;" class="adm-form-layout">
                <div>
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header"><h3><i class="fas fa-id-card"></i> Personal Information</h3></div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Full Name <span class="req">*</span></label>
                                    <input type="text" name="full_name" class="adm-input" required placeholder="Patient's full name"
                                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="adm-input" max="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Gender <span class="req">*</span></label>
                                    <select name="gender" class="adm-input" required>
                                        <option value="">— Select —</option>
                                        <option value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Blood Group</label>
                                    <select name="blood_group" class="adm-input">
                                        <option value="">— Unknown —</option>
                                        <?php foreach ($blood_groups as $bg): ?>
                                        <option value="<?php echo $bg; ?>" <?php echo (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Phone Number</label>
                                    <input type="tel" name="phone" class="adm-input" placeholder="0XXXXXXXXX"
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Email Address</label>
                                    <input type="email" name="email" class="adm-input" placeholder="patient@email.com (optional — creates login account)"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Home Address</label>
                                    <textarea name="address" class="adm-input" rows="2" placeholder="Street, Town, Region"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="adm-card">
                        <div class="adm-card-header"><h3><i class="fas fa-phone-alt"></i> Emergency Contact</h3></div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">
                                <div class="adm-form-group">
                                    <label class="adm-label">Contact Person Name</label>
                                    <input type="text" name="emergency_contact_name" class="adm-input" placeholder="Parent / Guardian / Spouse"
                                           value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Contact Phone</label>
                                    <input type="tel" name="emergency_contact_phone" class="adm-input" placeholder="0XXXXXXXXX"
                                           value="<?php echo htmlspecialchars($_POST['emergency_contact_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header"><h3><i class="fas fa-hospital-alt"></i> Admission Details</h3></div>
                        <div class="adm-card-body">
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label class="adm-label">Patient Type</label>
                                <select name="patient_type" class="adm-input">
                                    <?php foreach(['Outpatient','Inpatient','Emergency','Semi-Urgent'] as $pt): ?>
                                    <option value="<?php echo $pt; ?>" <?php echo (($_POST['patient_type'] ?? 'Outpatient') === $pt) ? 'selected' : ''; ?>><?php echo $pt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-label">Admission Date</label>
                                <input type="date" name="admit_date" class="adm-input"
                                       value="<?php echo htmlspecialchars($_POST['admit_date'] ?? date('Y-m-d')); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="adm-card" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:2rem;">
                        <div class="adm-card-body" style="text-align:center;padding:2rem 1.5rem;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,#E74C3C,#EC7063);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="fas fa-user-injured" style="color:#fff;font-size:2rem;"></i>
                            </div>
                            <p style="font-size:1.2rem;color:var(--text-secondary);">If email is provided, a <strong>patient</strong> login account will be created with default password <code>rmu@123</code>.</p>
                        </div>
                    </div>

                    <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.4rem;font-size:1.5rem;">
                        <i class="fas fa-save"></i> Register Patient
                    </button>
                    <a href="/RMU-Medical-Management-System/php/patient/patient.php"
                       class="adm-btn adm-btn-ghost" style="width:100%;justify-content:center;margin-top:1rem;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</main>

<style>
.adm-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.8rem;}
.adm-span-2{grid-column:span 2;}
.adm-form-group{display:flex;flex-direction:column;gap:.6rem;}
.adm-label{font-size:1.3rem;font-weight:600;color:var(--text-secondary);}
.adm-label .req{color:var(--danger);}
.adm-input{padding:1.1rem 1.4rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;font-size:1.4rem;color:var(--text-primary);background:var(--surface);outline:none;transition:var(--transition);width:100%;}
.adm-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(47,128,237,.1);}
textarea.adm-input{resize:vertical;}
@media(max-width:900px){.adm-form-layout{grid-template-columns:1fr!important;}.adm-form-grid{grid-template-columns:1fr!important;}.adm-span-2{grid-column:span 1;}}
</style>

<script>
const sidebar=document.getElementById('admSidebar');
const overlay=document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click',()=>{sidebar.classList.toggle('active');overlay.classList.toggle('active');});
overlay?.addEventListener('click',()=>{sidebar.classList.remove('active');overlay.classList.remove('active');});
const themeToggle=document.getElementById('themeToggle');
const themeIcon=document.getElementById('themeIcon');
const html=document.documentElement;
function applyTheme(t){html.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon';}
applyTheme(localStorage.getItem('rmu_theme')||'light');
themeToggle?.addEventListener('click',()=>applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
</script>
</body>
</html>
