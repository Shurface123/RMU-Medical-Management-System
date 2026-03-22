<?php
// ============================================================
// LAB DASHBOARD — TAB PROFILE (Module 15, Phase 9)
// Advanced 12-Section Technician Profile
// ============================================================
if (!isset($user_id)) { exit; }

$today = date('Y-m-d');

// ── Core profile row ──────────────────────────────────────
$prof_stmt = $conn->prepare("SELECT l.*, u.email, u.phone, u.created_at AS member_since, u.last_login
    FROM lab_technicians l JOIN users u ON l.user_id = u.id WHERE l.user_id = ? LIMIT 1");
$prof_stmt->bind_param("i", $user_id);
$prof_stmt->execute();
$profile = $prof_stmt->get_result()->fetch_assoc();
$prof_stmt->close();

if (!$profile) {
    echo "<div class='alert alert-warning'>Technician profile not initialized. Contact administrator.</div>";
    exit;
}
$tech_pk = (int)$profile['id'];

// ── Professional profile ──────────────────────────────────
$pp_stmt = $conn->prepare("SELECT * FROM lab_technician_professional_profile WHERE technician_id = ? LIMIT 1");
$pp_stmt->bind_param("i", $tech_pk);
$pp_stmt->execute();
$pro_profile = $pp_stmt->get_result()->fetch_assoc() ?? [];
$pp_stmt->close();

// ── Completeness ──────────────────────────────────────────
$cp_stmt = $conn->prepare("SELECT * FROM lab_technician_profile_completeness WHERE technician_id = ? LIMIT 1");
$cp_stmt->bind_param("i", $tech_pk);
$cp_stmt->execute();
$completeness_row = $cp_stmt->get_result()->fetch_assoc();
$cp_stmt->close();
$completeness_pct = (int)($completeness_row['overall_percentage'] ?? 0);

// ── Notification settings ─────────────────────────────────
$ns_stmt = $conn->prepare("SELECT * FROM lab_technician_settings WHERE technician_id = ? LIMIT 1");
$ns_stmt->bind_param("i", $tech_pk);
$ns_stmt->execute();
$notif_settings = $ns_stmt->get_result()->fetch_assoc() ?? [];
$ns_stmt->close();

// ── Qualifications ────────────────────────────────────────
$qual_stmt = $conn->prepare("SELECT * FROM lab_technician_qualifications WHERE technician_id = ? ORDER BY year_awarded DESC");
$qual_stmt->bind_param("i", $tech_pk);
$qual_stmt->execute();
$qualifications = $qual_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$qual_stmt->close();

// ── Certifications ────────────────────────────────────────
$cert_stmt = $conn->prepare("SELECT * FROM lab_technician_certifications WHERE technician_id = ? ORDER BY expiry_date ASC");
$cert_stmt->bind_param("i", $tech_pk);
$cert_stmt->execute();
$certifications = $cert_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cert_stmt->close();

// ── Documents ─────────────────────────────────────────────
$doc_stmt = $conn->prepare("SELECT * FROM lab_technician_documents WHERE technician_id = ? ORDER BY uploaded_at DESC");
$doc_stmt->bind_param("i", $tech_pk);
$doc_stmt->execute();
$documents = $doc_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$doc_stmt->close();

// ── Equipment assigned ────────────────────────────────────
$equip_res = mysqli_query($conn, "SELECT * FROM lab_equipment WHERE status NOT IN ('Decommissioned') ORDER BY next_calibration_date ASC LIMIT 10");

// ── Reagents ──────────────────────────────────────────────
$reag_res = mysqli_query($conn, "SELECT * FROM reagent_inventory ORDER BY status ASC LIMIT 10");

// ── Sessions ──────────────────────────────────────────────
$sess_stmt = $conn->prepare("SELECT * FROM lab_technician_sessions WHERE technician_id = ? ORDER BY last_active DESC LIMIT 10");
$sess_stmt->bind_param("i", $tech_pk);
$sess_stmt->execute();
$sessions = $sess_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sess_stmt->close();

// ── Audit trail (personal) ────────────────────────────────
$aud_stmt = $conn->prepare("SELECT * FROM lab_audit_trail WHERE technician_id = ? ORDER BY created_at DESC LIMIT 100");
$aud_stmt->bind_param("i", $user_id);
$aud_stmt->execute();
$audit_rows = $aud_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$aud_stmt->close();

// ── Age calculation ───────────────────────────────────────
$dob = $profile['date_of_birth'] ?? null;
$age = $dob ? (int)((time() - strtotime($dob)) / 31557600) : null;

// ── License expiry warning ────────────────────────────────
$lic_exp = $pro_profile['license_expiry_date'] ?? $profile['license_expiry'] ?? null;
$lic_days_left = $lic_exp ? (int)((strtotime($lic_exp) - time()) / 86400) : null;
$lic_warning = $lic_days_left !== null && $lic_days_left <= 60 && $lic_days_left >= 0;

// ── Active orders count ───────────────────────────────────
$active_orders_q = $conn->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=? AND status NOT IN ('Completed','Cancelled','Rejected')");
$active_orders_q->bind_param("i", $user_id);
$active_orders_q->execute();
$active_orders_count = (int)$active_orders_q->get_result()->fetch_row()[0];
$active_orders_q->close();

// ── Departments ───────────────────────────────────────────
$depts_res = mysqli_query($conn, "SELECT id, department_name FROM departments ORDER BY department_name");
$depts = [];
while($d = mysqli_fetch_assoc($depts_res)) $depts[] = $d;
?>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- PROFILE PAGE SHELL -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="sec-header">
    <h2><i class="fas fa-id-card-alt"></i> Advanced Technician Profile</h2>
    <div style="display:flex; gap:0.8rem; align-items:center;">
        <?php if($lic_warning): ?>
        <span class="adm-badge" style="background:var(--warning);color:white;font-size:0.85em;">
            <i class="fas fa-exclamation-triangle"></i> License expires in <?= $lic_days_left ?>d
        </span>
        <?php endif; ?>
        <span class="adm-badge adm-badge-primary"><?= $active_orders_count ?> Active Orders</span>
    </div>
</div>

<!-- Toast Container -->
<div id="profileToast" style="position:fixed;bottom:2rem;right:2rem;z-index:9999;min-width:280px;display:none;"></div>

<div style="display:grid;grid-template-columns:240px 1fr;gap:1.5rem;align-items:start;">

<!-- ─── LEFT: Section Pill Navigation ─── -->
<div class="info-card" style="padding:0;overflow:hidden;position:sticky;top:80px;">
    <div style="padding:1rem 1.2rem;background:var(--role-accent);color:white;">
        <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.08em;opacity:.85;">Profile Sections</div>
    </div>
    <nav id="profileNav" style="padding:0.5rem 0;">
        <?php
        $sections = [
            'sec-a' => ['fa-user-circle', 'Identity Card'],
            'sec-b' => ['fa-address-card', 'Personal Info'],
            'sec-c' => ['fa-briefcase-medical', 'Professional Profile'],
            'sec-d' => ['fa-certificate', 'Qualifications'],
            'sec-e' => ['fa-chart-bar', 'Performance Stats'],
            'sec-f' => ['fa-microscope', 'Equipment & Reagents'],
            'sec-g' => ['fa-calendar-alt', 'Shift & Availability'],
            'sec-h' => ['fa-shield-alt', 'Account & Security'],
            'sec-i' => ['fa-bell', 'Notifications'],
            'sec-j' => ['fa-folder-open', 'Documents'],
            'sec-k' => ['fa-history', 'My Audit Trail'],
            'sec-l' => ['fa-tasks', 'Profile Completeness'],
        ];
        foreach($sections as $id => [$icon, $label]):
        ?>
        <a href="javascript:void(0)" class="prof-nav-item <?= $id === 'sec-a' ? 'active' : '' ?>"
           onclick="showSection('<?= $id ?>', this)"
           style="display:flex;align-items:center;gap:0.8rem;padding:0.8rem 1.2rem;color:var(--text-primary);text-decoration:none;font-size:0.9rem;transition:all 0.2s;border-left:3px solid transparent;">
            <i class="fas <?= $icon ?>" style="width:16px;text-align:center;color:var(--role-accent);"></i>
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <!-- Completeness bar -->
    <div style="padding:1rem 1.2rem;border-top:1px solid var(--border);">
        <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.4rem;">Profile Complete</div>
        <div style="background:var(--surface-2);border-radius:4px;height:8px;overflow:hidden;">
            <div id="completeness-bar-side" style="width:<?= $completeness_pct ?>%;height:100%;background:var(--role-accent);border-radius:4px;transition:width 0.5s;"></div>
        </div>
        <div id="completeness-pct-side" style="font-size:0.8rem;font-weight:600;color:var(--role-accent);margin-top:0.3rem;"><?= $completeness_pct ?>%</div>
    </div>
</div>

<!-- ─── RIGHT: Section Panels ─── -->
<div id="profileContent">

<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION A: IDENTITY CARD -->
<!-- ══════════════════════════════════════════════════ -->
<div id="sec-a" class="profile-section">
    <div class="info-card" style="margin-bottom:1.5rem;">
        <div style="display:flex;gap:2rem;align-items:flex-start;flex-wrap:wrap;">
            <!-- Profile Photo -->
            <div style="text-align:center;flex-shrink:0;">
                <div style="position:relative;display:inline-block;">
                    <img id="profile-photo-img"
                         src="/RMU-Medical-Management-System/uploads/profiles/<?= e(!empty($profile['profile_photo']) ? $profile['profile_photo'] : 'default-avatar.png') ?>"
                         onerror="this.src='/RMU-Medical-Management-System/image/default-avatar.png'"
                         style="width:150px;height:150px;border-radius:50%;object-fit:cover;border:4px solid var(--role-accent);box-shadow:var(--shadow-md);">
                    <label for="photoInput" title="Change Photo"
                           style="position:absolute;bottom:4px;right:4px;width:36px;height:36px;border-radius:50%;background:var(--role-accent);color:white;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:var(--shadow-sm);">
                        <i class="fas fa-camera" style="font-size:0.85rem;"></i>
                    </label>
                    <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="uploadPhoto(this)">
                </div>
                <div style="margin-top:0.8rem;">
                    <!-- Availability Toggle -->
                    <select id="availabilitySelect" class="form-select" style="font-size:0.85rem;padding:0.3rem 0.6rem;" onchange="setAvailability(this.value)">
                        <?php foreach(['Available','Busy','On Break','Off Duty'] as $st):
                            $cur = $profile['availability_status'] ?? 'Available';
                            $col = ['Available'=>'#27ae60','Busy'=>'#e74c3c','On Break'=>'#f39c12','Off Duty'=>'#95a5a6'][$st] ?? '#888';
                        ?>
                        <option value="<?= $st ?>" <?= $cur===$st?'selected':'' ?> style="color:<?= $col ?>"><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <!-- Identity Details -->
            <div style="flex:1;min-width:200px;">
                <h2 style="margin:0 0 0.3rem;color:var(--text-primary);"><?= e($profile['full_name']) ?></h2>
                <div style="display:flex;gap:0.6rem;flex-wrap:wrap;margin-bottom:0.8rem;">
                    <span class="adm-badge" style="background:var(--role-accent-light);color:var(--role-accent);border:1px solid var(--role-accent);"><?= e($profile['designation'] ?? 'Lab Technician') ?></span>
                    <span class="adm-badge adm-badge-primary"><?= e($profile['specialization'] ?? 'General') ?></span>
                    <?php if(!empty($profile['lab_section'])): ?>
                    <span class="adm-badge" style="background:var(--surface-2);color:var(--text-secondary);border:1px solid var(--border);"><?= e($profile['lab_section']) ?></span>
                    <?php endif; ?>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.6rem;font-size:0.88rem;color:var(--text-secondary);">
                    <div><i class="fas fa-id-badge" style="color:var(--role-accent);width:16px;"></i> <strong>ID:</strong> <?= e($profile['technician_id'] ?? 'N/A') ?></div>
                    <div><i class="fas fa-envelope" style="color:var(--role-accent);width:16px;"></i> <?= e($profile['email'] ?? '') ?></div>
                    <div><i class="fas fa-phone" style="color:var(--role-accent);width:16px;"></i> <?= e($profile['phone'] ?? '') ?></div>
                    <?php if($age): ?>
                    <div><i class="fas fa-birthday-cake" style="color:var(--role-accent);width:16px;"></i> <?= $age ?> years old</div>
                    <?php endif; ?>
                    <div><i class="fas fa-calendar-plus" style="color:var(--role-accent);width:16px;"></i> Member since <?= $profile['member_since'] ? date('M Y', strtotime($profile['member_since'])) : 'N/A' ?></div>
                    <?php if(!empty($profile['last_login'])): ?>
                    <div><i class="fas fa-sign-in-alt" style="color:var(--role-accent);width:16px;"></i> Last login: <?= date('d M, h:i A', strtotime($profile['last_login'])) ?></div>
                    <?php endif; ?>
                </div>
                <?php if($lic_warning): ?>
                <div style="margin-top:1rem;padding:0.6rem 1rem;background:rgba(241,196,15,0.1);border-left:3px solid var(--warning);border-radius:4px;font-size:0.85rem;color:var(--warning);">
                    <i class="fas fa-exclamation-triangle"></i> <strong>License Expiry Warning:</strong> Your license expires on <?= date('d M Y', strtotime($lic_exp)) ?> (<?= $lic_days_left ?> days remaining).
                </div>
                <?php endif; ?>
            </div>
            <!-- Completeness -->
            <div style="flex-shrink:0;min-width:160px;text-align:center;">
                <canvas id="completenessDonut" width="140" height="140"></canvas>
                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.5rem;" id="completeness-label">Profile <?= $completeness_pct ?>% Complete</div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION B: PERSONAL INFORMATION -->
<!-- ══════════════════════════════════════════════════ -->
<div id="sec-b" class="profile-section" style="display:none;">
    <div class="info-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fas fa-address-card" style="color:var(--role-accent);"></i> Personal Information</h3>
            <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="savePersonalInfo()"><i class="fas fa-save"></i> Save Changes</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;">
            <div class="form-group">
                <label>Full Name <span style="color:var(--danger)">*</span></label>
                <input type="text" id="pi_full_name" class="form-control" value="<?= e($profile['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" id="pi_dob" class="form-control" value="<?= e($profile['date_of_birth'] ?? '') ?>" onchange="calcAge()">
            </div>
            <div class="form-group">
                <label>Age (Auto-calculated)</label>
                <input type="text" id="pi_age" class="form-control" value="<?= $age ? $age . ' years' : '' ?>" readonly style="background:var(--surface-2);">
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select id="pi_gender" class="form-select">
                    <?php foreach(['Male','Female','Other'] as $g): ?>
                    <option value="<?= $g ?>" <?= ($profile['gender'] ?? '') === $g ? 'selected':'' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nationality</label>
                <input type="text" id="pi_nationality" class="form-control" value="<?= e($profile['nationality'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Marital Status</label>
                <select id="pi_marital" class="form-select">
                    <?php foreach(['Single','Married','Divorced','Widowed'] as $m): ?>
                    <option value="<?= $m ?>" <?= ($profile['marital_status'] ?? '') === $m ? 'selected':'' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Primary Phone <span style="color:var(--danger)">*</span></label>
                <input type="tel" id="pi_phone" class="form-control" value="<?= e($profile['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Secondary Phone</label>
                <input type="tel" id="pi_phone2" class="form-control" value="<?= e($profile['phone2'] ?? '') ?>">
            </div>
            <div class="form-group" style="grid-column:span 2;">
                <label>Residential Address</label>
                <input type="text" id="pi_address" class="form-control" value="<?= e($profile['address'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>City / Town</label>
                <input type="text" id="pi_city" class="form-control" value="<?= e($profile['city'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Region / State</label>
                <input type="text" id="pi_region" class="form-control" value="<?= e($profile['region'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Country</label>
                <input type="text" id="pi_country" class="form-control" value="<?= e($profile['country'] ?? 'Ghana') ?>">
            </div>
            <div class="form-group">
                <label>Postal Code</label>
                <input type="text" id="pi_postal" class="form-control" value="<?= e($profile['postal_code'] ?? '') ?>">
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION C: PROFESSIONAL PROFILE -->
<!-- ══════════════════════════════════════════════════ -->
<div id="sec-c" class="profile-section" style="display:none;">
    <div class="info-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fas fa-briefcase-medical" style="color:var(--role-accent);"></i> Professional Profile</h3>
            <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="saveProfessionalProfile()"><i class="fas fa-save"></i> Save Changes</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;">
            <div class="form-group">
                <label>Lab Specialization</label>
                <select id="pp_spec" class="form-select">
                    <?php foreach(['Clinical Chemistry','Hematology','Microbiology','Immunology','Histopathology','Serology','Urinalysis','Molecular Biology','Parasitology','General'] as $sp): ?>
                    <option value="<?= $sp ?>" <?= ($pro_profile['specialization'] ?? $profile['specialization'] ?? '') === $sp ? 'selected':'' ?>><?= $sp ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sub-Specialization</label>
                <input type="text" id="pp_sub_spec" class="form-control" value="<?= e($pro_profile['sub_specialization'] ?? '') ?>" placeholder="Optional">
            </div>
            <div class="form-group">
                <label>Department</label>
                <select id="pp_dept" class="form-select">
                    <option value="">— Select Department —</option>
                    <?php foreach($depts as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($pro_profile['department_id'] ?? '') == $d['id'] ? 'selected':'' ?>><?= e($d['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Designation / Title</label>
                <select id="pp_desig" class="form-select">
                    <?php foreach(['Lab Technician','Senior Lab Technician','Lab Supervisor','Lab Manager','Chief Medical Laboratory Scientist'] as $ds): ?>
                    <option value="<?= $ds ?>" <?= ($pro_profile['designation'] ?? $profile['designation'] ?? '') === $ds ? 'selected':'' ?>><?= $ds ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Years of Experience</label>
                <input type="number" id="pp_yoe" class="form-control" min="0" max="60" value="<?= e($pro_profile['years_of_experience'] ?? $profile['years_of_experience'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>License Number <span style="color:var(--danger)">*</span></label>
                <input type="text" id="pp_lic" class="form-control" value="<?= e($pro_profile['license_number'] ?? $profile['license_number'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>License Issuing Body</label>
                <input type="text" id="pp_lic_body" class="form-control" value="<?= e($pro_profile['license_issuing_body'] ?? '') ?>" placeholder="e.g. Allied Health Professions Council">
            </div>
            <div class="form-group">
                <label>License Expiry Date <?= $lic_warning ? '<span class="adm-badge" style="background:var(--warning);color:white;font-size:0.7em;">EXPIRING SOON</span>' : '' ?></label>
                <input type="date" id="pp_lic_exp" class="form-control" value="<?= e($lic_exp ?? '') ?>" style="<?= $lic_warning ? 'border-color:var(--warning);' : '' ?>">
            </div>
            <div class="form-group">
                <label>School / University Attended</label>
                <input type="text" id="pp_inst" class="form-control" value="<?= e($pro_profile['institution_attended'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Year of Graduation</label>
                <input type="number" id="pp_grad" class="form-control" min="1970" max="<?= date('Y') ?>" value="<?= e($pro_profile['graduation_year'] ?? '') ?>">
            </div>
            <div class="form-group" style="grid-column:span 2;">
                <label>Postgraduate / Specialty Training Details</label>
                <textarea id="pp_pg" class="form-control" rows="2"><?= e($pro_profile['postgraduate_details'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="grid-column:span 2;">
                <label>Languages Spoken (comma-separated)</label>
                <input type="text" id="pp_langs" class="form-control" value="<?= e(is_array(json_decode($pro_profile['languages_spoken'] ?? 'null', true)) ? implode(', ', json_decode($pro_profile['languages_spoken'], true)) : '') ?>" placeholder="English, French, Twi">
            </div>
            <div class="form-group" style="grid-column:span 2;">
                <label>Professional Bio / Summary</label>
                <textarea id="pp_bio" class="form-control" rows="4" placeholder="Brief professional summary visible to admin..."><?= e($pro_profile['bio'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
</div>

<?php // SECTION D-L will be appended via include ?>
<?php include 'tab_profile_part2.php'; ?>
