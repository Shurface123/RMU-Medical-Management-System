<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include 'db_conn.php';

$active_page = 'doctors';
$page_title  = 'Add Doctor';
include '../includes/_sidebar.php';

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $email        = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $phone        = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $gender       = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
    $specialization = mysqli_real_escape_string($conn, trim($_POST['specialization'] ?? ''));
    $experience   = (int)($_POST['experience_years'] ?? 0);
    $avail_days   = mysqli_real_escape_string($conn, trim($_POST['available_days'] ?? ''));
    $schedule     = mysqli_real_escape_string($conn, trim($_POST['schedule_notes'] ?? ''));
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if (!$name || !$email || !$specialization) {
        $error = 'Name, Email, and Specialization are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check email uniqueness
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'A user with this email already exists.';
        } else {
            // Generate doctor_id
            $last_doc = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM doctors"))[0] ?? 0;
            $doctor_id = 'DOC-' . str_pad($last_doc + 1, 4, '0', STR_PAD_LEFT);

            // Default password
            $default_pass = password_hash('rmu@123', PASSWORD_DEFAULT);

            // Insert into users table
            $sql_user = "INSERT INTO users (name, email, phone, gender, password, role, is_active)
                         VALUES ('$name','$email','$phone','$gender','$default_pass','doctor',1)";
            if (mysqli_query($conn, $sql_user)) {
                $user_id = mysqli_insert_id($conn);
                // Insert into doctors table
                $sql_doc = "INSERT INTO doctors (user_id, doctor_id, specialization, experience_years, available_days, schedule_notes, is_available)
                            VALUES ($user_id,'$doctor_id','$specialization',$experience,'$avail_days','$schedule',$is_available)";
                if (mysqli_query($conn, $sql_doc)) {
                    header('Location: /RMU-Medical-Management-System/php/Doctor/doctor.php?success=Doctor+registered+successfully');
                    exit();
                } else {
                    // Rollback user
                    mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");
                    $error = 'Doctor record error: ' . mysqli_error($conn);
                }
            } else {
                $error = 'User creation error: ' . mysqli_error($conn);
            }
        }
    }
}

$specializations = ['General Medicine', 'Emergency Medicine', 'Cardiology', 'Dermatology', 'Gynaecology',
                    'Internal Medicine', 'Neurology', 'Ophthalmology', 'Orthopaedics', 'Paediatrics',
                    'Psychiatry', 'Radiology', 'Sports Medicine', 'Surgery', 'Urology', 'Other'];
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-md" style="color:var(--primary);margin-right:.8rem;"></i>Add Doctor</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1><i class="fas fa-user-plus" style="color:var(--primary);margin-right:.6rem;"></i>Register New Doctor</h1>
                <p>Create login credentials and medical profile for a new doctor.</p>
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
        <div class="adm-alert" style="background:var(--info-light);border-left:4px solid var(--info);color:var(--info);margin-bottom:1.5rem;">
            <i class="fas fa-info-circle"></i>
            <div>A system account will be created for this doctor. Default password: <strong>rmu@123</strong> — they can change it after first login.</div>
        </div>

        <form method="POST" action="" id="addDoctorForm" novalidate>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;" class="adm-form-layout">
                <div>
                    <!-- Personal Info -->
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header"><h3><i class="fas fa-id-card"></i> Personal Information</h3></div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Full Name <span class="req">*</span></label>
                                    <input type="text" name="name" class="adm-input" required
                                           placeholder="Dr. First Last"
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Email Address <span class="req">*</span></label>
                                    <input type="email" name="email" class="adm-input" required
                                           placeholder="doctor@rmu.edu.gh"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Phone Number</label>
                                    <input type="tel" name="phone" class="adm-input"
                                           placeholder="0XXXXXXXXX"
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
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
                            </div>
                        </div>
                    </div>

                    <!-- Professional Info -->
                    <div class="adm-card">
                        <div class="adm-card-header"><h3><i class="fas fa-stethoscope"></i> Professional Details</h3></div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">
                                <div class="adm-form-group">
                                    <label class="adm-label">Specialization <span class="req">*</span></label>
                                    <select name="specialization" class="adm-input" required>
                                        <option value="">— Select Specialization —</option>
                                        <?php foreach ($specializations as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo (($_POST['specialization'] ?? '') === $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Years of Experience</label>
                                    <input type="number" name="experience_years" class="adm-input" min="0" max="60"
                                           placeholder="0"
                                           value="<?php echo htmlspecialchars($_POST['experience_years'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Available Days</label>
                                    <div style="display:flex;flex-wrap:wrap;gap:.8rem;margin-top:.4rem;">
                                        <?php
                                        $avail_post = $_POST['available_days'] ?? '';
                                        foreach ($days as $d):
                                            $checked = str_contains($avail_post, $d) ? 'checked' : '';
                                        ?>
                                        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.6rem 1.2rem;border:1.5px solid var(--border);border-radius:8px;font-size:1.3rem;transition:var(--transition);" class="day-pill">
                                            <input type="checkbox" name="available_day[]" value="<?php echo $d; ?>" <?php echo $checked; ?> style="accent-color:var(--primary);">
                                            <?php echo $d; ?>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="available_days" id="availableDaysHidden" value="<?php echo htmlspecialchars($avail_post); ?>">
                                </div>
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Schedule Notes</label>
                                    <textarea name="schedule_notes" class="adm-input" rows="2"
                                              placeholder="e.g. Morning shifts 8AM–12PM, afternoon on Wednesday..."><?php echo htmlspecialchars($_POST['schedule_notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header"><h3><i class="fas fa-toggle-on"></i> Availability Status</h3></div>
                        <div class="adm-card-body" style="text-align:center;">
                            <label class="adm-switch-wrap">
                                <input type="checkbox" name="is_available" id="availSwitch" <?php echo (!isset($_POST['name']) || isset($_POST['is_available'])) ? 'checked' : ''; ?>>
                                <div class="adm-switch"></div>
                                <span class="adm-switch-label">Available for appointments</span>
                            </label>
                        </div>
                    </div>

                    <div class="adm-card" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:2rem;">
                        <div class="adm-card-body" style="text-align:center;padding:2rem 1.5rem;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="fas fa-user-md" style="color:#fff;font-size:2.2rem;"></i>
                            </div>
                            <p style="font-size:1.2rem;color:var(--text-secondary);line-height:1.8;">An account will be created with role <strong>doctor</strong>. Default password: <code style="background:var(--surface);padding:.2rem .6rem;border-radius:6px;">rmu@123</code></p>
                        </div>
                    </div>

                    <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.4rem;font-size:1.5rem;">
                        <i class="fas fa-user-plus"></i> Register Doctor
                    </button>
                    <a href="/RMU-Medical-Management-System/php/Doctor/doctor.php"
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
.day-pill:has(input:checked){border-color:var(--primary);background:var(--primary-light);}
.adm-switch-wrap{display:flex;flex-direction:column;align-items:center;gap:1rem;cursor:pointer;}
.adm-switch{position:relative;width:52px;height:28px;background:var(--border);border-radius:50px;transition:var(--transition);}
.adm-switch::after{content:'';position:absolute;left:3px;top:3px;width:22px;height:22px;background:#fff;border-radius:50%;transition:var(--transition);}
input[type=checkbox]:checked ~ .adm-switch{background:var(--primary);}
input[type=checkbox]:checked ~ .adm-switch::after{left:27px;}
.adm-switch-wrap input{display:none;}
.adm-switch-label{font-size:1.3rem;font-weight:600;color:var(--text-primary);}
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

// Collect available days into hidden field
document.querySelector('form').addEventListener('submit', function() {
    const checked = [...document.querySelectorAll('input[name="available_day[]"]:checked')].map(cb => cb.value);
    document.getElementById('availableDaysHidden').value = checked.join(', ');
});
</script>
</body>
</html>
