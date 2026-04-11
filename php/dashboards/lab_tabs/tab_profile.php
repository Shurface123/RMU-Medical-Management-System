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
$active_orders_q = $conn->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=? AND order_status NOT IN ('Completed','Cancelled','Rejected')");
$active_orders_q->bind_param("i", $user_id);
$active_orders_q->execute();
$active_orders_count = (int)$active_orders_q->get_result()->fetch_row()[0];
$active_orders_q->close();

// ── Departments ───────────────────────────────────────────
$depts_res = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name");
$depts = [];
while($d = mysqli_fetch_assoc($depts_res)) $depts[] = $d;
?>

<div class="sec-header">
    <h2 style="font-size: 1.8rem; font-weight: 700;"><i class="fas fa-id-card-alt"></i> Technician Identity Vault</h2>
    <div style="display:flex; gap:1.2rem; align-items:center;">
        <?php if($lic_warning): ?>
        <span class="adm-badge" style="background:rgba(231,76,60,0.1); color:var(--danger); border:1px solid var(--danger); font-weight:700;">
            <i class="fas fa-exclamation-triangle"></i> License Renewal Critical: <?= $lic_days_left ?>d
        </span>
        <?php endif; ?>
        <span class="adm-badge adm-badge-primary" style="font-weight:700;"><i class="fas fa-microscope"></i> <?= $active_orders_count ?> Active Assignments</span>
    </div>
</div>

<!-- Toast Container -->
<div id="profileToast" style="position:fixed; bottom:2.5rem; right:2.5rem; z-index:9999; min-width:320px; display:none;"></div>

<div style="display:grid; grid-template-columns:280px 1fr; gap:2.5rem; align-items:start;">

<!-- ─── LEFT: Section Navigation ─── -->
<div class="info-card" style="padding:0; overflow:hidden; position:sticky; top:100px; border:1px solid var(--border);">
    <div style="padding:1.5rem; background:var(--primary); color:white;">
        <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:.15em; font-weight:800; opacity:.9; margin-bottom:0.2rem;">Registry Control</div>
        <div style="font-size:1.1rem; font-weight:700;">Profile Modules</div>
    </div>
    <nav id="profileNav" style="padding:1rem 0;">
        <?php
        $sections = [
            'sec-a' => ['fa-user-astronaut', 'Identity Matrix'],
            'sec-b' => ['fa-address-card', 'Personal Dossier'],
            'sec-c' => ['fa-briefcase-medical', 'Expertise Profile'],
            'sec-d' => ['fa-certificate', 'Credentials & Awards'],
            'sec-e' => ['fa-chart-line', 'Performance Metrics'],
            'sec-f' => ['fa-microscope', 'Asset Custody'],
            'sec-g' => ['fa-calendar-day', 'Shift Schedule'],
            'sec-h' => ['fa-shield-halved', 'Security & Access'],
            'sec-i' => ['fa-bell', 'Alert Preferences'],
            'sec-j' => ['fa-folder-tree', 'Document Archive'],
            'sec-k' => ['fa-file-shield', 'Action History'],
            'sec-l' => ['fa-tasks-alt', 'Integrity Status'],
        ];
        foreach($sections as $id => [$icon, $label]):
        ?>
        <a href="javascript:void(0)" class="prof-nav-item <?= $id === 'sec-a' ? 'active' : '' ?>"
           onclick="showSection('<?= $id ?>', this)"
           style="display:flex; align-items:center; gap:1rem; padding:1.1rem 1.5rem; color:var(--text-secondary); text-decoration:none; font-size:0.95rem; font-weight:600; transition:var(--transition); border-left:4px solid transparent;">
            <i class="fas <?= $icon ?>" style="width:20px; text-align:center; color:var(--primary);"></i>
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <!-- Completeness bar -->
    <div style="padding:1.5rem; border-top:1px solid var(--border); background:var(--surface-2);">
        <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Data Integrity</div>
            <div id="completeness-pct-side" style="font-size:0.85rem; font-weight:800; color:var(--primary);"><?= $completeness_pct ?>%</div>
        </div>
        <div style="background:rgba(0,0,0,0.05); border-radius:30px; height:8px; overflow:hidden;">
            <div id="completeness-bar-side" style="width:<?= $completeness_pct ?>%; height:100%; background:var(--primary); border-radius:30px; transition:width 0.8s cubic-bezier(0.4, 0, 0.2, 1); box-shadow:0 0 10px rgba(13,148,136,0.3);"></div>
        </div>
    </div>
</div>

<!-- ─── RIGHT: Section Panels ─── -->
<div id="profileContent">

<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION A: IDENTITY MATRIX -->
<!-- ══════════════════════════════════════════════════ -->
<div id="sec-a" class="profile-section">
    <div class="info-card" style="margin-bottom:2rem; padding:2.5rem;">
        <div style="display:flex; gap:3rem; align-items:center; flex-wrap:wrap;">
            <!-- Profile Photo -->
            <div style="text-align:center; flex-shrink:0;">
                <div style="position:relative; display:inline-block;">
                    <div style="padding: 6px; background: white; border-radius: 50%; box-shadow: var(--shadow-lg); border: 1px solid var(--border);">
                        <img id="profile-photo-img"
                             src="/RMU-Medical-Management-System/uploads/profiles/<?= e(!empty($profile['profile_photo']) ? $profile['profile_photo'] : 'default-avatar.png') ?>"
                             onerror="this.src='/RMU-Medical-Management-System/image/default-avatar.png'"
                             style="width:180px; height:180px; border-radius:50%; object-fit:cover;">
                    </div>
                    <label for="photoInput" title="Update Biometrics"
                           style="position:absolute; bottom:12px; right:12px; width:45px; height:45px; border-radius:50%; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:var(--shadow-md); border:4px solid white; transition:var(--transition);">
                        <i class="fas fa-camera-retro" style="font-size:1.1rem;"></i>
                    </label>
                    <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="uploadPhoto(this)">
                </div>
                <div style="margin-top:1.5rem;">
                    <!-- Availability Toggle -->
                    <select id="availabilitySelect" class="form-select" style="font-size:0.9rem; font-weight:700; border-radius:30px; padding:0.6rem 1.2rem; background:var(--surface-2); text-align:center;" onchange="setAvailability(this.value)">
                        <?php foreach(['Available','Busy','On Break','Off Duty'] as $st):
                            $cur = $profile['availability_status'] ?? 'Available';
                            $col = ['Available'=>'#0d9488','Busy'=>'#e74c3c','On Break'=>'#f39c12','Off Duty'=>'#64748b'][$st] ?? '#64748b';
                        ?>
                        <option value="<?= $st ?>" <?= $cur===$st?'selected':'' ?> style="color:<?= $col ?>; font-weight:700;"><?= $st ?> State</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <!-- Identity Details -->
            <div style="flex:1; min-width:300px;">
                <h1 style="margin:0 0 0.5rem; font-size:2.4rem; font-weight:800; letter-spacing:-0.02em; color:var(--text-primary);"><?= e($profile['full_name']) ?></h1>
                <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
                    <span class="adm-badge" style="background:rgba(13,148,136,0.1); color:var(--primary); border:1px solid var(--primary); padding:0.5rem 1rem; font-weight:700;"><?= e($profile['designation'] ?? 'Lab Technician') ?></span>
                    <span class="adm-badge adm-badge-primary" style="padding:0.5rem 1rem; font-weight:700;"><?= e($profile['specialization'] ?? 'General Diagnostics') ?></span>
                    <?php if(!empty($profile['lab_section'])): ?>
                    <span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); padding:0.5rem 1rem; font-weight:700;"><?= e($profile['lab_section']) ?> Section</span>
                    <?php endif; ?>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; font-size:1rem; color:var(--text-secondary); background:var(--surface-2); padding:1.5rem; border-radius:15px; border:1px solid var(--border);">
                    <div style="display:flex; align-items:center; gap:0.8rem;"><i class="fas fa-fingerprint" style="color:var(--primary); font-size:1.1rem;"></i> <span><strong>Entity ID:</strong> <?= e($profile['technician_id'] ?? 'N/A') ?></span></div>
                    <div style="display:flex; align-items:center; gap:0.8rem;"><i class="fas fa-envelope-open-text" style="color:var(--primary); font-size:1.1rem;"></i> <span><?= e($profile['email'] ?? '') ?></span></div>
                    <div style="display:flex; align-items:center; gap:0.8rem;"><i class="fas fa-phone-volume" style="color:var(--primary); font-size:1.1rem;"></i> <span><?= e($profile['phone'] ?? '') ?></span></div>
                    <?php if($age): ?>
                    <div style="display:flex; align-items:center; gap:0.8rem;"><i class="fas fa-cake-candles" style="color:var(--primary); font-size:1.1rem;"></i> <span><?= $age ?> Solar Years</span></div>
                    <?php endif; ?>
                    <div style="display:flex; align-items:center; gap:0.8rem;"><i class="fas fa-calendar-check" style="color:var(--primary); font-size:1.1rem;"></i> <span>Registered <?= $profile['member_since'] ? date('M Y', strtotime($profile['member_since'])) : 'N/A' ?></span></div>
                    <?php if(!empty($profile['last_login'])): ?>
                    <div style="display:flex; align-items:center; gap:0.8rem;"><i class="fas fa-satellite-dish" style="color:var(--primary); font-size:1.1rem;"></i> <span>Active: <?= date('d M, h:i A', strtotime($profile['last_login'])) ?></span></div>
                    <?php endif; ?>
                </div>
                <?php if($lic_warning): ?>
                <div style="margin-top:1.5rem; padding:1rem 1.5rem; background:rgba(231,76,60,0.05); border-left:5px solid var(--danger); border-radius:10px; font-size:0.95rem; color:var(--danger); font-weight:600; display:flex; align-items:center; gap:1rem;">
                    <i class="fas fa-radiation" style="font-size:1.4rem;"></i>
                    <div>
                        <strong>Credential Expiry Imminent:</strong> Your operational license expires on <span style="text-decoration:underline;"><?= date('d M Y', strtotime($lic_exp)) ?></span> (<?= $lic_days_left ?> days remaining).
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <!-- Completeness -->
            <div style="flex-shrink:0; min-width:180px; text-align:center; padding:1.5rem; background:var(--surface-2); border-radius:20px; border:1px solid var(--border);">
                <canvas id="completenessDonut" width="140" height="140"></canvas>
                <div style="font-size:0.9rem; font-weight:800; color:var(--primary); margin-top:1rem;" id="completeness-label"><?= $completeness_pct ?>% Credential Path</div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION B: PERSONAL DOSSIER -->
<!-- ══════════════════════════════════════════════════ -->
<div id="sec-b" class="profile-section" style="display:none;">
    <div class="info-card" style="padding:2.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-weight:800; font-size:1.6rem;"><i class="fas fa-address-card" style="color:var(--primary); margin-right:.8rem;"></i> Personal Dossier</h3>
            <button class="btn btn-primary" onclick="savePersonalInfo()"><span class="btn-text"><i class="fas fa-save"></i> Synchronize Data</span></button>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Personnel Full Name <span style="color:var(--danger)">*</span></label>
                <input type="text" id="pi_full_name" class="form-control" value="<?= e($profile['full_name']) ?>" style="padding:0.8rem; font-weight:600;" required>
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Date of Birth</label>
                <input type="date" id="pi_dob" class="form-control" value="<?= e($profile['date_of_birth'] ?? '') ?>" onchange="calcAge()" style="padding:0.8rem;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Age (Verified Calculus)</label>
                <input type="text" id="pi_age" class="form-control" value="<?= $age ? $age . ' cycles' : '' ?>" readonly style="background:var(--surface-2); font-weight:700; color:var(--primary); padding:0.8rem;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Gender Identity</label>
                <select id="pi_gender" class="form-select" style="padding:0.8rem; font-weight:600;">
                    <?php foreach(['Male','Female','Other'] as $g): ?>
                    <option value="<?= $g ?>" <?= ($profile['gender'] ?? '') === $g ? 'selected':'' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Origin Nationality</label>
                <input type="text" id="pi_nationality" class="form-control" value="<?= e($profile['nationality'] ?? '') ?>" style="padding:0.8rem;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Matrimonial Status</label>
                <select id="pi_marital" class="form-select" style="padding:0.8rem; font-weight:600;">
                    <?php foreach(['Single','Married','Divorced','Widowed'] as $m): ?>
                    <option value="<?= $m ?>" <?= ($profile['marital_status'] ?? '') === $m ? 'selected':'' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Primary Comm Channel <span style="color:var(--danger)">*</span></label>
                <input type="tel" id="pi_phone" class="form-control" value="<?= e($profile['phone'] ?? '') ?>" style="padding:0.8rem; font-weight:600;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Secondary Backup Channel</label>
                <input type="tel" id="pi_phone2" class="form-control" value="<?= e($profile['phone2'] ?? '') ?>" style="padding:0.8rem;">
            </div>
            <div class="form-group" style="grid-column:span 2;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Residential Coordinates</label>
                <input type="text" id="pi_address" class="form-control" value="<?= e($profile['address'] ?? '') ?>" style="padding:0.8rem;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Jurisdiction / Town</label>
                <input type="text" id="pi_city" class="form-control" value="<?= e($profile['city'] ?? '') ?>" style="padding:0.8rem;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Administrative Region</label>
                <input type="text" id="pi_region" class="form-control" value="<?= e($profile['region'] ?? '') ?>" style="padding:0.8rem;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Territory</label>
                <input type="text" id="pi_country" class="form-control" value="<?= e($profile['country'] ?? 'Ghana') ?>" style="padding:0.8rem;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Postal Vector</label>
                <input type="text" id="pi_postal" class="form-control" value="<?= e($profile['postal_code'] ?? '') ?>" style="padding:0.8rem;">
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION C: EXPERTISE PROFILE -->
<!-- ══════════════════════════════════════════════════ -->
<div id="sec-c" class="profile-section" style="display:none;">
    <div class="info-card" style="padding:2.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-weight:800; font-size:1.6rem;"><i class="fas fa-microscope" style="color:var(--primary); margin-right:.8rem;"></i> Professional Expertise Profile</h3>
            <button class="btn btn-primary" onclick="saveProfessionalProfile()"><span class="btn-text"><i class="fas fa-save"></i> Update Registry</span></button>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Core Specialization</label>
                <select id="pp_spec" class="form-select" style="padding:0.8rem; font-weight:600;">
                    <?php foreach(['Clinical Chemistry','Hematology','Microbiology','Immunology','Histopathology','Serology','Urinalysis','Molecular Biology','Parasitology','General Diagnostics'] as $sp): ?>
                    <option value="<?= $sp ?>" <?= ($pro_profile['specialization'] ?? $profile['specialization'] ?? '') === $sp ? 'selected':'' ?>><?= $sp ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Niche Sub-Specialization</label>
                <input type="text" id="pp_sub_spec" class="form-control" value="<?= e($pro_profile['sub_specialization'] ?? '') ?>" placeholder="Optional expertise niche..." style="padding:0.8rem;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Faculty Department</label>
                <select id="pp_dept" class="form-select" style="padding:0.8rem; font-weight:600;">
                    <option value="">— Assign Department —</option>
                    <?php foreach($depts as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($d['id'] == ($pro_profile['department_id'] ?? $profile['department_id'] ?? '')) ? 'selected' : '' ?>>
                        <?= e($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Operational Title</label>
                <select id="pp_desig" class="form-select" style="padding:0.8rem; font-weight:600;">
                    <?php foreach(['Lab Technician','Senior Lab Technician','Lab Supervisor','Lab Manager','Chief Medical Laboratory Scientist'] as $ds): ?>
                    <option value="<?= $ds ?>" <?= ($pro_profile['designation'] ?? $profile['designation'] ?? '') === $ds ? 'selected':'' ?>><?= $ds ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Professional Seniority (Cycles)</label>
                <input type="number" id="pp_yoe" class="form-control" min="0" max="60" value="<?= e($pro_profile['years_of_experience'] ?? $profile['years_of_experience'] ?? '') ?>" style="padding:0.8rem; font-weight:700;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">License Identifier <span style="color:var(--danger)">*</span></label>
                <input type="text" id="pp_lic" class="form-control" value="<?= e($pro_profile['license_number'] ?? $profile['license_number'] ?? '') ?>" style="padding:0.8rem; font-family:monospace; font-weight:700; letter-spacing:0.05em; color:var(--primary);">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Credentialing Authority</label>
                <input type="text" id="pp_lic_body" class="form-control" value="<?= e($pro_profile['license_issuing_body'] ?? '') ?>" placeholder="e.g. Allied Health Professions Council" style="padding:0.8rem;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Validation Expiry Date <?= $lic_warning ? '<span class="adm-badge" style="background:var(--danger); color:white; font-size:0.75rem; margin-left:.5rem;">CRITICAL</span>' : '' ?></label>
                <input type="date" id="pp_lic_exp" class="form-control" value="<?= e($lic_exp ?? '') ?>" style="padding:0.8rem; <?= $lic_warning ? 'border:2px solid var(--danger); background:rgba(231,76,60,0.05);' : '' ?>">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Alma Mater / University</label>
                <input type="text" id="pp_inst" class="form-control" value="<?= e($pro_profile['institution_attended'] ?? '') ?>" style="padding:0.8rem;">
            </div>
            <div class="form-group">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Conferral Year</label>
                <input type="number" id="pp_grad" class="form-control" min="1970" max="<?= date('Y') ?>" value="<?= e($pro_profile['graduation_year'] ?? '') ?>" style="padding:0.8rem;">
            </div>
            <div class="form-group" style="grid-column:span 2;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Advanced Research / Specialty Training</label>
                <textarea id="pp_pg" class="form-control" rows="2" style="padding:0.8rem;"><?= e($pro_profile['postgraduate_details'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="grid-column:span 2;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Linguistic Matrix (comma-separated)</label>
                <input type="text" id="pp_langs" class="form-control" value="<?= e(is_array(json_decode($pro_profile['languages_spoken'] ?? 'null', true)) ? implode(', ', json_decode($pro_profile['languages_spoken'], true)) : '') ?>" placeholder="English, French, Twi..." style="padding:0.8rem;">
            </div>
            <div class="form-group" style="grid-column:span 2;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.6rem;">Technician Bio / Mission Statement</label>
                <textarea id="pp_bio" class="form-control" rows="4" placeholder="Brief professional narrative visible to administration..." style="padding:1rem;"><?= e($pro_profile['bio'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
</div>

<?php // SECTION D-L will be appended via include ?>
<?php include 'tab_profile_part2.php'; ?>
