<?php
// register.php — Multi-Step Registration Portal (Phase 3)
// ============================================================
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once 'db_conn.php';
require_once 'includes/reg_config.php';

// Generate CSRF token
if (empty($_SESSION['_reg_csrf'])) {
    $_SESSION['_reg_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['_reg_csrf'];
$site_key = RECAPTCHA_SITE_KEY;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — RMU Medical Sickbay</title>
<link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($site_key) ?>"></script>
<style>
:root {
    --primary: #2F80ED;
    --primary-dark: #2366CC;
    --secondary: #56CCF2;
    --success: #27ae60;
    --danger: #e74c3c;
    --warning: #f39c12;
    --text-dark: #2c3e50;
    --text-muted: #7f8c8d;
    --border: #e0e0e0;
    --white: #ffffff;
    --bg-gradient: linear-gradient(135deg, #1C3A6B 0%, #2F80ED 55%, #56CCF2 100%);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg-gradient);padding:2rem 1rem;position:relative;overflow-x:hidden;}
body::before{content:'';position:absolute;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,.06) 1px,transparent 1px);background-size:50px 50px;animation:bgMove 25s linear infinite;pointer-events:none;}
@keyframes bgMove{0%{transform:translate(0,0)}100%{transform:translate(50px,50px)}}

/* ── Container ── */
.reg-container{position:relative;z-index:10;background:#fff;border-radius:28px;box-shadow:0 20px 70px rgba(47,128,237,.25);width:100%;max-width:700px;padding:0;overflow:hidden;animation:slideIn .5s ease-out;}
@keyframes slideIn{from{opacity:0;transform:translateY(-30px)}to{opacity:1;transform:translateY(0)}}

/* ── Header bar ── */
.reg-header{background:var(--bg-gradient);padding:2rem 2.5rem 1.8rem;position:relative;overflow:hidden;}
.reg-header::after{content:'';position:absolute;right:-40px;top:-40px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.07);}
.reg-header-inner{display:flex;align-items:center;gap:1.2rem;position:relative;}
.reg-logo-icon{width:56px;height:56px;background:rgba(255,255,255,.2);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;border:1.5px solid rgba(255,255,255,.3);flex-shrink:0;}
.reg-header-text h1{font-size:1.6rem;font-weight:700;color:#fff;margin:0 0 .2rem;}
.reg-header-text p{font-size:0.95rem;color:rgba(255,255,255,.8);margin:0;}

/* ── Progress bar ── */
.reg-progress{background:rgba(0,0,0,.1);padding:1.2rem 2.5rem 0;}
.progress-steps{display:flex;align-items:center;gap:.3rem;padding-bottom:1.2rem;border-bottom:1px solid rgba(255,255,255,.15);}
.p-step{display:flex;flex-direction:column;align-items:center;gap:.4rem;flex:1;position:relative;}
.p-step:not(:last-child)::after{content:'';position:absolute;left:calc(50% + 18px);top:16px;width:calc(100% - 36px);height:2px;background:rgba(255,255,255,.2);z-index:0;}
.p-step.done:not(:last-child)::after{background:rgba(255,255,255,.7);}
.p-bubble{width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.3);color:rgba(255,255,255,.7);font-size:0.9rem;font-weight:700;display:flex;align-items:center;justify-content:center;z-index:1;transition:all .3s;}
.p-step.active .p-bubble{background:#fff;color:var(--primary);border-color:#fff;box-shadow:0 4px 16px rgba(47,128,237,.4);}
.p-step.done .p-bubble{background:var(--success);border-color:var(--success);color:#fff;}
.p-label{font-size:.8rem;color:rgba(255,255,255,.6);font-weight:500;text-align:center;white-space:nowrap;}
.p-step.active .p-label{color:#fff;font-weight:700;}
.p-step.done .p-label{color:rgba(255,255,255,.85);}

/* ── Body ── */
.reg-body{padding:2.5rem;}

/* ── Step panels ── */
.step-panel{display:none;animation:fadeStep .3s ease;}
.step-panel.active{display:block;}
@keyframes fadeStep{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}

/* ── Role cards (Step 1) ── */
.step-title{font-size:1.3rem;font-weight:700;color:var(--text-dark);margin-bottom:.4rem;}
.step-sub{font-size:0.95rem;color:var(--text-muted);margin-bottom:2rem;}
.roles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1.2rem;margin-bottom:1.5rem;}
.role-card{border:2px solid var(--border);border-radius:18px;padding:1.8rem 1.2rem;text-align:center;cursor:pointer;transition:all .25s;background:#fff;position:relative;overflow:hidden;}
.role-card::before{content:'';position:absolute;inset:0;background:var(--rc-clr,var(--primary));opacity:0;transition:opacity .25s;}
.role-card:hover::before,.role-card.selected::before{opacity:.06;}
.role-card.selected{border-color:var(--rc-clr,var(--primary));box-shadow:0 6px 24px rgba(0,0,0,.12);}
.role-card i{font-size:2rem;margin-bottom:.8rem;color:var(--rc-clr,var(--primary));position:relative;}
.role-card span{font-size:1rem;font-weight:600;color:var(--text-dark);display:block;position:relative;}
.role-card .rc-badge{font-size:0.8rem;color:var(--text-muted);display:block;margin-top:.3rem;position:relative;}

/* Patient type sub-selection */
.patient-type-group{display:flex;gap:1rem;margin-top:1rem;display:none;}
.pt-card{flex:1;border:2px solid var(--border);border-radius:14px;padding:1.2rem;text-align:center;cursor:pointer;transition:all .25s;}
.pt-card.selected{border-color:var(--primary);background:#EBF3FF;}
.pt-card i{font-size:1.4rem;color:var(--primary);margin-bottom:.5rem;display:block;}
.pt-card span{font-size:1rem;font-weight:600;color:var(--text-dark);}

/* ── Form elements ── */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;}
.form-group{margin-bottom:1.6rem;}
.form-group label{display:block;font-size:0.9rem;font-weight:600;color:var(--text-dark);margin-bottom:.6rem;}
.form-group label .req{color:var(--danger);}
.input-wrap{position:relative;}
.input-wrap i.field-icon{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:1.1rem;pointer-events:none;}
.form-control{width:100%;padding:0.8rem 1rem 0.8rem 2.8rem;font-size:0.95rem;border:2px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;transition:all .25s;background:#fff;color:var(--text-dark);}
.form-control:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 4px rgba(47,128,237,.1);}
.form-control.valid{border-color:var(--success);}
.form-control.invalid{border-color:var(--danger);}
.form-control.no-icon{padding-left:1rem;}
select.form-control{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%237f8c8d' d='M6 9L1 4h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 1rem center;padding-right:2.5rem;}
.field-msg{font-size:0.85rem;margin-top:.45rem;min-height:1.2rem;transition:all .2s;}
.field-msg.ok{color:var(--success);}
.field-msg.err{color:var(--danger);}

/* ── Password strength ── */
.pw-meter{height:5px;background:#e0e0e0;border-radius:3px;margin-top:.8rem;overflow:hidden;}
.pw-bar{height:100%;width:0;border-radius:3px;transition:all .35s;}
.pw-bar.s0{width:0}
.pw-bar.s1{width:20%;background:#e74c3c;}
.pw-bar.s2{width:40%;background:#e67e22;}
.pw-bar.s3{width:60%;background:#f39c12;}
.pw-bar.s4{width:80%;background:#7dcea0;}
.pw-bar.s5{width:100%;background:#27ae60;}
.pw-label{font-size:0.85rem;color:var(--text-muted);margin-top:.4rem;}
.pw-checks{display:grid;grid-template-columns:1fr 1fr;gap:.4rem;margin-top:.8rem;}
.pw-check{font-size:0.85rem;color:#aaa;display:flex;align-items:center;gap:.5rem;transition:color .2s;}
.pw-check.met{color:var(--success);}
.pw-check i{width:14px;}

/* ── Profile photo ── */
.photo-upload-area{border:2px dashed var(--border);border-radius:14px;padding:2rem;text-align:center;cursor:pointer;transition:all .25s;position:relative;}
.photo-upload-area:hover{border-color:var(--primary);background:#EBF3FF;}
.photo-preview{width:80px;height:80px;border-radius:50%;object-fit:cover;margin:0 auto .8rem;display:none;}
.photo-upload-area i{font-size:2rem;color:var(--primary);display:block;margin-bottom:.5rem;}
.photo-upload-area p{font-size:0.95rem;color:var(--text-muted);}
.photo-upload-area input{position:absolute;inset:0;opacity:0;cursor:pointer;}

/* ── Review panel ── */
.review-section{background:#F4F8FF;border-radius:14px;padding:1.5rem;margin-bottom:1.2rem;}
.review-section h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:1rem;display:flex;align-items:center;gap:.6rem;}
.review-grid{display:grid;grid-template-columns:1fr 1fr;gap:.6rem .8rem;}
.review-item label{font-size:0.85rem;color:var(--text-muted);font-weight:500;display:block;}
.review-item span{font-size:0.95rem;color:var(--text-dark);font-weight:600;}

/* ── Alert boxes ── */
.alert{padding:1rem 1.2rem;border-radius:12px;font-size:0.95rem;margin-bottom:1.5rem;display:none;}
.alert.show{display:flex;align-items:flex-start;gap:.8rem;}
.alert-err{background:#FDEDEC;color:#c0392b;border-left:4px solid #e74c3c;}
.alert-info{background:#EBF5FB;color:#1a5276;border-left:4px solid #2980b9;}

/* ── Buttons ── */
.btn-row{display:flex;gap:1rem;margin-top:2rem;justify-content:space-between;}
.btn{padding:0.8rem 1.6rem;font-size:1rem;font-weight:600;border-radius:10px;border:none;cursor:pointer;transition:all .25s;display:inline-flex;align-items:center;gap:.6rem;font-family:'Poppins',sans-serif;}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;box-shadow:0 6px 20px rgba(47,128,237,.3);}
.btn-primary:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 10px 28px rgba(47,128,237,.4);}
.btn-primary:disabled{background:#bdc3c7;cursor:not-allowed;box-shadow:none;}
.btn-outline{background:#fff;color:var(--primary);border:2px solid var(--primary);}
.btn-outline:hover{background:var(--primary);color:#fff;}
.btn-sm{padding:0.6rem 1rem;font-size:0.9rem;}

/* ── Footer ── */
.reg-footer{text-align:center;padding:1.5rem 2.5rem 2rem;border-top:1px solid #f0f0f0;font-size:0.95rem;color:var(--text-muted);}
.reg-footer a{color:var(--primary);font-weight:600;text-decoration:none;}

/* ── Back to home ── */
.back-home{position:fixed;top:1.5rem;left:1.5rem;z-index:100;}
.back-home a{display:flex;align-items:center;gap:.6rem;padding:.6rem 1.2rem;background:rgba(255,255,255,.9);color:var(--primary);text-decoration:none;border-radius:50px;font-size:0.95rem;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.1);transition:all .25s;}
.back-home a:hover{background:#fff;transform:translateY(-2px);}

@media(max-width:600px){.form-row,.review-grid,.pw-checks{grid-template-columns:1fr;}.roles-grid{grid-template-columns:1fr 1fr;}.reg-body{padding:1.5rem;}.reg-header{padding:1.6rem 1.5rem 1.4rem;}.progress-steps{gap:.1rem;}.p-label{font-size:.7rem;}}
</style>
</head>
<body>

<div class="back-home">
    <a href="/RMU-Medical-Management-System/html/index.html">
        <i class="fas fa-arrow-left"></i><span>Back to Home</span>
    </a>
</div>

<div class="reg-container">

    <!-- Header -->
    <div class="reg-header">
        <div class="reg-header-inner">
            <div class="reg-logo-icon"><i class="fas fa-hospital-user"></i></div>
            <div class="reg-header-text">
                <h1>Create Account</h1>
                <p>Join RMU Medical Sickbay</p>
            </div>
        </div>
        <!-- Progress Steps -->
        <div class="reg-progress">
            <div class="progress-steps">
                <?php
                $steps = ['Role','Personal Info','Details','Password','Review'];
                foreach ($steps as $i => $lbl):
                    $n = $i + 1;
                ?>
                <div class="p-step <?= $n===1?'active':'' ?>" id="ps<?= $n ?>">
                    <div class="p-bubble"><?= $n ?></div>
                    <div class="p-label"><?= $lbl ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Body -->
    <div class="reg-body">
        <div class="alert alert-err" id="globalErr"><i class="fas fa-exclamation-circle"></i><span id="globalErrMsg"></span></div>

        <form id="regForm" method="POST" action="register_handler.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="recaptcha_token" id="recaptchaToken">
            <input type="hidden" name="selected_role" id="selectedRole">
            <input type="hidden" name="patient_type" id="patientTypeHidden">

            <!-- ══════ STEP 1: ROLE SELECTION ══════ -->
            <div class="step-panel active" id="step1">
                <h2 class="step-title">Who are you registering as?</h2>
                <p class="step-sub">Select your role to get started. Admin accounts are created by the administrator.</p>

                <div class="roles-grid">
                    <?php foreach (REGISTERABLE_ROLES as $roleKey => $roleInfo): ?>
                    <div class="role-card" data-role="<?= $roleKey ?>"
                         style="--rc-clr:<?= $roleInfo['color'] ?>">
                        <i class="fas <?= $roleInfo['icon'] ?>"></i>
                        <span><?= $roleInfo['label'] ?></span>
                        <?php if (in_array($roleKey, APPROVAL_REQUIRED_ROLES)): ?>
                        <em class="rc-badge">Requires approval</em>
                        <?php else: ?>
                        <em class="rc-badge">Instant access</em>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="patient-type-group" id="patientTypeGroup">
                    <div class="pt-card" data-pt="student">
                        <i class="fas fa-user-graduate"></i>
                        <span>Student</span>
                        <small style="font-size:1.1rem;color:var(--text-muted);">@st.rmu.edu.gh</small>
                    </div>
                    <div class="pt-card" data-pt="staff">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Staff / Lecturer</span>
                        <small style="font-size:1.1rem;color:var(--text-muted);">@rmu.edu.gh</small>
                    </div>
                </div>

                <div class="btn-row" style="justify-content:flex-end;">
                    <button type="button" class="btn btn-primary" id="step1Next" disabled>
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ══════ STEP 2: PERSONAL INFO ══════ -->
            <div class="step-panel" id="step2">
                <h2 class="step-title">Personal Information</h2>
                <p class="step-sub">Please enter your personal details below.</p>

                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="req">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-user field-icon"></i>
                            <input type="text" class="form-control" name="first_name" id="firstName" placeholder="John" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="req">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-user field-icon"></i>
                            <input type="text" class="form-control" name="last_name" id="lastName" placeholder="Doe" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope field-icon"></i>
                        <input type="email" class="form-control" name="email" id="emailField"
                               placeholder="your@email.com" required autocomplete="email">
                    </div>
                    <div class="field-msg" id="emailMsg"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number <span class="req">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-phone field-icon"></i>
                            <input type="tel" class="form-control" name="phone" id="phoneField"
                                   placeholder="0501234567" required>
                        </div>
                        <div class="field-msg" id="phoneMsg"></div>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth <span class="req">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-calendar field-icon"></i>
                            <input type="date" class="form-control" name="dob" id="dobField"
                                   max="<?= date('Y-m-d', strtotime('-5 years')) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Gender <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-venus-mars field-icon"></i>
                        <select class="form-control" name="gender" id="genderField" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Profile Photo <small style="font-size:1.1rem;color:var(--text-muted)">(Optional — JPG/PNG, max 2MB)</small></label>
                    <div class="photo-upload-area" id="photoArea">
                        <img id="photoPreview" class="photo-preview" src="" alt="Preview">
                        <i class="fas fa-cloud-upload-alt" id="photoIcon"></i>
                        <p id="photoText">Click to upload your photo</p>
                        <input type="file" name="profile_photo" id="photoInput"
                               accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="field-msg" id="photoMsg"></div>
                </div>

                <div class="btn-row">
                    <button type="button" class="btn btn-outline" onclick="goStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button type="button" class="btn btn-primary" id="step2Next" disabled>Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- ══════ STEP 3: ROLE-SPECIFIC DETAILS ══════ -->
            <div class="step-panel" id="step3">
                <h2 class="step-title">Role-Specific Details</h2>
                <p class="step-sub">Additional information required for your role.</p>

                <!-- Patient fields -->
                <div id="fields_patient" class="role-fields" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Student / Staff ID <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-id-card field-icon"></i>
                                <input type="text" class="form-control" name="patient_id_number" placeholder="e.g. RMU/ST/2024/001">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Department / Faculty <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-building field-icon"></i>
                                <input type="text" class="form-control" name="department" placeholder="e.g. Faculty of Medicine">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Blood Type <small style="color:var(--text-muted)">(Optional)</small></label>
                            <div class="input-wrap">
                                <i class="fas fa-tint field-icon"></i>
                                <select class="form-control" name="blood_type">
                                    <option value="">Select</option>
                                    <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                                    <option><?= $bt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Name</label>
                            <div class="input-wrap">
                                <i class="fas fa-user-shield field-icon"></i>
                                <input type="text" class="form-control" name="emergency_name" placeholder="Contact Name">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact Phone</label>
                        <div class="input-wrap">
                            <i class="fas fa-phone-alt field-icon"></i>
                            <input type="tel" class="form-control" name="emergency_phone" placeholder="0501234567">
                        </div>
                    </div>
                </div>

                <!-- Doctor fields -->
                <div id="fields_doctor" class="role-fields" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Medical License Number <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-certificate field-icon"></i>
                                <input type="text" class="form-control" name="license_number" placeholder="MDC/GH/...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Specialization <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-stethoscope field-icon"></i>
                                <input type="text" class="form-control" name="specialization" placeholder="e.g. General Medicine">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Department <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-hospital field-icon"></i>
                                <input type="text" class="form-control" name="department" placeholder="e.g. Internal Medicine">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Years of Experience</label>
                            <div class="input-wrap">
                                <i class="fas fa-history field-icon"></i>
                                <input type="number" class="form-control" name="experience_years" min="0" max="60" placeholder="5">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nurse fields -->
                <div id="fields_nurse" class="role-fields" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nursing License Number <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-certificate field-icon"></i>
                                <input type="text" class="form-control" name="license_number" placeholder="NMC/GH/...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Specialization <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-notes-medical field-icon"></i>
                                <input type="text" class="form-control" name="specialization" placeholder="e.g. Paediatrics">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Ward / Department <span class="req">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-bed field-icon"></i>
                            <input type="text" class="form-control" name="department" placeholder="e.g. General Ward">
                        </div>
                    </div>
                </div>

                <!-- Lab Technician fields -->
                <div id="fields_lab_technician" class="role-fields" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Lab License Number <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-certificate field-icon"></i>
                                <input type="text" class="form-control" name="license_number" placeholder="LAB/GH/...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Lab Specialization <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-flask field-icon"></i>
                                <select class="form-control" name="specialization">
                                    <option value="">Select</option>
                                    <?php foreach(['Hematology','Microbiology','Clinical Chemistry','Immunology','Parasitology','Histopathology','Other'] as $ls): ?>
                                    <option><?= $ls ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Department / Lab Section <span class="req">*</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-vials field-icon"></i>
                            <input type="text" class="form-control" name="department" placeholder="e.g. Main Laboratory">
                        </div>
                    </div>
                </div>

                <!-- Pharmacist fields -->
                <div id="fields_pharmacist" class="role-fields" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pharmacy License Number <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="fas fa-certificate field-icon"></i>
                                <input type="text" class="form-control" name="license_number" placeholder="PC/GH/...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Specialization</label>
                            <div class="input-wrap">
                                <i class="fas fa-pills field-icon"></i>
                                <input type="text" class="form-control" name="specialization" placeholder="e.g. Clinical Pharmacy">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <div class="input-wrap">
                            <i class="fas fa-hospital field-icon"></i>
                            <input type="text" class="form-control" name="department" placeholder="e.g. Pharmacy Dept.">
                        </div>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="button" class="btn btn-outline" onclick="goStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button type="button" class="btn btn-primary" id="step3Next">Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- ══════ STEP 4: PASSWORD ══════ -->
            <div class="step-panel" id="step4">
                <h2 class="step-title">Set Your Password</h2>
                <p class="step-sub">Create a strong, secure password for your account.</p>

                <div class="form-group">
                    <label>Username <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-at field-icon"></i>
                        <input type="text" class="form-control" name="username" id="usernameField"
                               placeholder="Choose a username" required autocomplete="username">
                    </div>
                    <div class="field-msg" id="usernameMsg"></div>
                </div>

                <div class="form-group">
                    <label>Password <span class="req">*</span></label>
                    <div class="input-wrap" style="position:relative;">
                        <i class="fas fa-lock field-icon"></i>
                        <input type="password" class="form-control" name="password" id="pwField"
                               placeholder="Create a strong password" required autocomplete="new-password">
                        <button type="button" class="pw-toggle" onclick="togglePw('pwField',this)"
                                style="position:absolute;right:1.2rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1.5rem;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="pw-meter"><div class="pw-bar" id="pwBar"></div></div>
                    <div class="pw-label" id="pwLabel">Enter a password</div>
                    <div class="pw-checks">
                        <div class="pw-check" id="ck-len"><i class="fas fa-circle-xmark"></i> Min 8 characters</div>
                        <div class="pw-check" id="ck-up"><i class="fas fa-circle-xmark"></i> Uppercase letter</div>
                        <div class="pw-check" id="ck-lo"><i class="fas fa-circle-xmark"></i> Lowercase letter</div>
                        <div class="pw-check" id="ck-num"><i class="fas fa-circle-xmark"></i> Number (0–9)</div>
                        <div class="pw-check" id="ck-sym"><i class="fas fa-circle-xmark"></i> Special character</div>
                        <div class="pw-check" id="ck-name"><i class="fas fa-circle-xmark"></i> Not your name/email</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password <span class="req">*</span></label>
                    <div class="input-wrap" style="position:relative;">
                        <i class="fas fa-lock field-icon"></i>
                        <input type="password" class="form-control" name="confirm_password" id="cpwField"
                               placeholder="Re-enter your password" required autocomplete="new-password">
                        <button type="button" class="pw-toggle" onclick="togglePw('cpwField',this)"
                                style="position:absolute;right:1.2rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1.5rem;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="field-msg" id="cpwMsg"></div>
                </div>

                <div class="form-group" style="display:flex;align-items:flex-start;gap:1rem;">
                    <input type="checkbox" name="terms" id="termsCheck"
                           style="width:18px;height:18px;margin-top:.3rem;flex-shrink:0;cursor:pointer;">
                    <label for="termsCheck" style="font-size:1.3rem;color:var(--text-dark);cursor:pointer;font-weight:400;">
                        I agree to the <a href="#" style="color:var(--primary);font-weight:600;">Terms & Conditions</a> and
                        <a href="#" style="color:var(--primary);font-weight:600;">Privacy Policy</a>
                    </label>
                </div>

                <div class="btn-row">
                    <button type="button" class="btn btn-outline" onclick="goStep(3)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button type="button" class="btn btn-primary" id="step4Next" disabled>Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- ══════ STEP 5: REVIEW ══════ -->
            <div class="step-panel" id="step5">
                <h2 class="step-title">Review Your Information</h2>
                <p class="step-sub">Please confirm all details before submitting.</p>

                <div class="review-section">
                    <h3><i class="fas fa-user-tag"></i> Role</h3>
                    <div class="review-grid">
                        <div class="review-item"><label>Selected Role</label><span id="rv-role">—</span></div>
                        <div class="review-item" id="rv-pt-wrap"><label>Patient Type</label><span id="rv-pt">—</span></div>
                    </div>
                </div>

                <div class="review-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="review-grid">
                        <div class="review-item"><label>Full Name</label><span id="rv-name">—</span></div>
                        <div class="review-item"><label>Email</label><span id="rv-email">—</span></div>
                        <div class="review-item"><label>Phone</label><span id="rv-phone">—</span></div>
                        <div class="review-item"><label>Date of Birth</label><span id="rv-dob">—</span></div>
                        <div class="review-item"><label>Gender</label><span id="rv-gender">—</span></div>
                        <div class="review-item"><label>Username</label><span id="rv-username">—</span></div>
                    </div>
                </div>

                <div class="review-section" id="rv-role-section">
                    <h3><i class="fas fa-briefcase-medical"></i> Role Details</h3>
                    <div class="review-grid" id="rv-role-details"></div>
                </div>

                <div class="alert alert-info show" style="margin-bottom:0;">
                    <i class="fas fa-info-circle"></i>
                    <span>After submission, a <strong>6-digit OTP</strong> will be sent to your email for verification.</span>
                </div>

                <div class="btn-row">
                    <button type="button" class="btn btn-outline" onclick="goStep(4)"><i class="fas fa-arrow-left"></i> Back & Edit</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Submit Registration
                    </button>
                </div>
            </div>

        </form>
    </div>

    <div class="reg-footer">
        Already have an account? <a href="index.php">Login here</a>
    </div>
</div>

<script>
// ── State ────────────────────────────────────────────────────
let currentStep = 1;
let selectedRole = '';
let patientType  = '';
let emailValid   = false;
let emailTimer   = null;

// ── Step navigation ───────────────────────────────────────────
function goStep(n) {
    document.getElementById('step' + currentStep).classList.remove('active');
    document.getElementById('step' + n).classList.add('active');
    // Progress bubbles
    for (let i = 1; i <= 5; i++) {
        const el = document.getElementById('ps' + i);
        el.classList.remove('active','done');
        if (i < n)       el.classList.add('done');
        else if (i === n) el.classList.add('active');
    }
    // Scroll top of container
    document.querySelector('.reg-container').scrollIntoView({behavior:'smooth'});
    currentStep = n;
}

// ── Step 1: Role selection ────────────────────────────────────
document.querySelectorAll('.role-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        selectedRole = card.dataset.role;
        document.getElementById('selectedRole').value = selectedRole;
        const ptg = document.getElementById('patientTypeGroup');
        if (selectedRole === 'patient') {
            ptg.style.display = 'flex';
            patientType = '';
            document.getElementById('patientTypeHidden').value = '';
            document.getElementById('step1Next').disabled = true;
        } else {
            ptg.style.display = 'none';
            patientType = '';
            document.getElementById('patientTypeHidden').value = '';
            document.getElementById('step1Next').disabled = false;
        }
    });
});

document.querySelectorAll('.pt-card').forEach(c => {
    c.addEventListener('click', () => {
        document.querySelectorAll('.pt-card').forEach(x => x.classList.remove('selected'));
        c.classList.add('selected');
        patientType = c.dataset.pt;
        document.getElementById('patientTypeHidden').value = patientType;
        document.getElementById('step1Next').disabled = false;
        // Update email placeholder
        const ph = patientType === 'student' ? 'you@st.rmu.edu.gh' : 'you@rmu.edu.gh';
        document.getElementById('emailField').placeholder = ph;
    });
});

document.getElementById('step1Next').addEventListener('click', () => goStep(2));

// ── Step 2: Email validation (real-time) ──────────────────────
const emailField = document.getElementById('emailField');
emailField.addEventListener('input', () => {
    clearTimeout(emailTimer);
    const msg = document.getElementById('emailMsg');
    const val = emailField.value.trim();
    emailValid = false;
    msg.className = 'field-msg';
    emailField.classList.remove('valid','invalid');

    if (!val) { checkStep2(); return; }

    // Instant domain check for patients
    if (selectedRole === 'patient') {
        const rule = patientType === 'student'
            ? '@st.rmu.edu.gh' : '@rmu.edu.gh';
        if (!val.toLowerCase().endsWith(rule)) {
            msg.className = 'field-msg err';
            msg.textContent = patientType === 'student'
                ? 'Student patients must use email ending in @st.rmu.edu.gh'
                : 'Staff/Lecturer patients must use email ending in @rmu.edu.gh';
            emailField.classList.add('invalid');
            checkStep2(); return;
        }
    }

    // Standard format check
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        msg.className = 'field-msg err';
        msg.textContent = 'Invalid email format';
        emailField.classList.add('invalid');
        checkStep2(); return;
    }

    msg.textContent = 'Checking...';
    emailTimer = setTimeout(() => {
        const fd = new FormData();
        fd.append('email', val);
        fd.append('role', selectedRole);
        fd.append('patient_type', patientType);
        fetch('ajax/check_email_reg.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    msg.className = 'field-msg ok';
                    msg.textContent = '✓ Email is available';
                    emailField.classList.add('valid');
                    emailValid = true;
                } else {
                    msg.className = 'field-msg err';
                    msg.textContent = d.msg;
                    emailField.classList.add('invalid');
                    emailValid = false;
                }
                checkStep2();
            })
            .catch(() => { emailValid = true; checkStep2(); });
    }, 600);
});

// Phone validation
document.getElementById('phoneField').addEventListener('input', function() {
    const v = this.value.replace(/\D/,'');
    const msg = document.getElementById('phoneMsg');
    if (v.length >= 10) {
        msg.className = 'field-msg ok'; msg.textContent = '✓ Looks good';
        this.classList.add('valid');
    } else if (v.length > 0) {
        msg.className = 'field-msg err'; msg.textContent = 'Enter a valid phone number (min 10 digits)';
        this.classList.remove('valid');
    } else { msg.textContent = ''; this.classList.remove('valid'); }
    checkStep2();
});

// Photo upload preview
document.getElementById('photoInput').addEventListener('change', function() {
    const file = this.files[0];
    const msg  = document.getElementById('photoMsg');
    if (!file) return;
    if (file.size > 2*1024*1024) {
        msg.className='field-msg err'; msg.textContent='File must be under 2MB'; this.value=''; return;
    }
    if (!['image/jpeg','image/png','image/webp'].includes(file.type)) {
        msg.className='field-msg err'; msg.textContent='Only JPG/PNG/WebP allowed'; this.value=''; return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('photoPreview');
        prev.src = e.target.result; prev.style.display='block';
        document.getElementById('photoIcon').style.display='none';
        document.getElementById('photoText').textContent='Photo selected ✓';
    };
    reader.readAsDataURL(file);
    msg.className='field-msg ok'; msg.textContent='✓ Photo ready';
});

function checkStep2() {
    const fn = document.getElementById('firstName').value.trim();
    const ln = document.getElementById('lastName').value.trim();
    const em = emailField.value.trim();
    const ph = document.getElementById('phoneField').value.trim();
    const db = document.getElementById('dobField').value;
    const gd = document.getElementById('genderField').value;
    const phOk = ph.replace(/\D/g,'').length >= 10;
    document.getElementById('step2Next').disabled = !(fn && ln && em && phOk && db && gd && emailValid);
}
['firstName','lastName','dobField','genderField'].forEach(id => {
    document.getElementById(id).addEventListener('input', checkStep2);
    document.getElementById(id).addEventListener('change', checkStep2);
});

document.getElementById('step2Next').addEventListener('click', () => {
    // Show correct role fields
    document.querySelectorAll('.role-fields').forEach(d => d.style.display='none');
    const fd = document.getElementById('fields_' + selectedRole);
    if (fd) fd.style.display='block';
    goStep(3);
});

// ── Step 3 → 4 ────────────────────────────────────────────────
document.getElementById('step3Next').addEventListener('click', () => goStep(4));

// ── Step 4: Password strength ─────────────────────────────────
const pwField  = document.getElementById('pwField');
const cpwField = document.getElementById('cpwField');
const labels   = ['Very Weak','Weak','Fair','Strong','Very Strong'];

function checkPw() {
    const pw   = pwField.value;
    const name = (document.getElementById('firstName').value + document.getElementById('lastName').value).toLowerCase();
    const em   = document.getElementById('emailField').value.toLowerCase().split('@')[0];

    const checks = {
        len:  pw.length >= 8,
        up:   /[A-Z]/.test(pw),
        lo:   /[a-z]/.test(pw),
        num:  /[0-9]/.test(pw),
        sym:  /[!@#$%^&*()\-_=+\[\]{}|;:'",.<>?/\\`~]/.test(pw),
        name: pw.length > 0 && !pw.toLowerCase().includes(name) && !pw.toLowerCase().includes(em)
    };
    const score = Object.values(checks).filter(Boolean).length;

    // Update bar
    const bar = document.getElementById('pwBar');
    bar.className = 'pw-bar s' + score;
    document.getElementById('pwLabel').textContent = pw.length ? labels[Math.min(score,4)] : 'Enter a password';

    // Update check items
    Object.keys(checks).forEach(k => {
        const el = document.getElementById('ck-'+k);
        if (!el) return;
        const icon = el.querySelector('i');
        el.classList.toggle('met', checks[k]);
        icon.className = checks[k] ? 'fas fa-circle-check' : 'fas fa-circle-xmark';
    });

    checkPwMatch();
    return Object.values(checks).every(Boolean);
}

function checkPwMatch() {
    const pw  = pwField.value;
    const cpw = cpwField.value;
    const msg = document.getElementById('cpwMsg');
    if (cpw.length === 0) { msg.textContent=''; return; }
    if (pw === cpw) {
        msg.className='field-msg ok'; msg.textContent='✓ Passwords match';
        cpwField.classList.add('valid');
    } else {
        msg.className='field-msg err'; msg.textContent='✗ Passwords do not match';
        cpwField.classList.remove('valid');
    }
    checkStep4();
}

pwField.addEventListener('input', () => { checkPw(); checkStep4(); });
cpwField.addEventListener('input', checkPwMatch);
document.getElementById('termsCheck').addEventListener('change', checkStep4);
document.getElementById('usernameField').addEventListener('input', checkStep4);

function checkStep4() {
    const allPw   = checkPw();
    const match   = pwField.value === cpwField.value && cpwField.value.length > 0;
    const terms   = document.getElementById('termsCheck').checked;
    const uname   = document.getElementById('usernameField').value.trim().length >= 3;
    document.getElementById('step4Next').disabled = !(allPw && match && terms && uname);
}

function togglePw(id, btn) {
    const f = document.getElementById(id);
    f.type = f.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').className = f.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

document.getElementById('step4Next').addEventListener('click', () => {
    buildReview();
    goStep(5);
});

// ── Step 5: Build review ──────────────────────────────────────
function buildReview() {
    const roleLabels = {
        patient:'Patient', doctor:'Doctor', nurse:'Nurse',
        lab_technician:'Lab Technician', pharmacist:'Pharmacist'
    };
    const ptLabels = {student:'Student', staff:'Staff / Lecturer'};

    document.getElementById('rv-role').textContent = roleLabels[selectedRole] || selectedRole;
    const ptWrap = document.getElementById('rv-pt-wrap');
    if (selectedRole === 'patient') {
        ptWrap.style.display='block';
        document.getElementById('rv-pt').textContent = ptLabels[patientType] || patientType;
    } else { ptWrap.style.display='none'; }

    document.getElementById('rv-name').textContent =
        document.getElementById('firstName').value + ' ' + document.getElementById('lastName').value;
    document.getElementById('rv-email').textContent    = document.getElementById('emailField').value;
    document.getElementById('rv-phone').textContent    = document.getElementById('phoneField').value;
    document.getElementById('rv-dob').textContent      = document.getElementById('dobField').value;
    document.getElementById('rv-gender').textContent   = document.getElementById('genderField').value;
    document.getElementById('rv-username').textContent = document.getElementById('usernameField').value;

    // Role-specific details
    const fields = document.getElementById('fields_' + selectedRole);
    const grid   = document.getElementById('rv-role-details');
    grid.innerHTML = '';
    if (fields) {
        fields.querySelectorAll('input,select').forEach(el => {
            const v = el.value.trim();
            if (!v) return;
            const label = el.closest('.form-group').querySelector('label').textContent.trim().replace(' *','');
            grid.innerHTML += `<div class="review-item"><label>${label}</label><span>${v}</span></div>`;
        });
    }
    if (!grid.innerHTML) document.getElementById('rv-role-section').style.display='none';
}

// ── Final submit with reCAPTCHA ───────────────────────────────
document.getElementById('regForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

    grecaptcha.ready(() => {
        grecaptcha.execute('<?= htmlspecialchars($site_key) ?>', {action: 'register'})
            .then(token => {
                document.getElementById('recaptchaToken').value = token;
                this.submit();
            })
            .catch(() => {
                showGlobalErr('reCAPTCHA verification failed. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Registration';
            });
    });
});

function showGlobalErr(msg) {
    const el = document.getElementById('globalErr');
    document.getElementById('globalErrMsg').textContent = msg;
    el.classList.add('show');
    el.scrollIntoView({behavior:'smooth'});
}

// ── Show URL error messages ───────────────────────────────────
const params = new URLSearchParams(location.search);
if (params.get('error')) showGlobalErr(params.get('error'));
</script>
</body>
</html>
